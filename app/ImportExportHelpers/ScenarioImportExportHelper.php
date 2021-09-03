<?php


namespace App\ImportExportHelpers;

use App\Console\Facades\ImportExportSerializer;
use App\Http\Controllers\API\ScenariosController;
use Illuminate\Support\Collection;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\Core\Conversation\Conversation;
use OpenDialogAi\Core\Conversation\DataClients\Serializers\Normalizers\ImportExport\ScenarioNormalizer;
use OpenDialogAi\Core\Conversation\Exceptions\DuplicateConversationObjectOdIdException;
use OpenDialogAi\Core\Conversation\Facades\ConversationDataClient;
use OpenDialogAi\Core\Conversation\Facades\ScenarioDataClient;
use OpenDialogAi\Core\Conversation\Intent;
use OpenDialogAi\Core\Conversation\Scenario;
use OpenDialogAi\Core\Conversation\Scene;
use OpenDialogAi\Core\Conversation\Turn;

class ScenarioImportExportHelper extends BaseImportExportHelper
{
    const SCENARIO_RESOURCE_DIRECTORY = 'scenarios';
    const SCENARIO_FILE_EXTENSION = ".scenario.json";

    const CONFIGURATIONS = 'configurations';

    /**
     * @return string
     */
    public static function getScenariosPath(): string
    {
        return self::SCENARIO_RESOURCE_DIRECTORY;
    }

    /**
     * @param  string  $name
     *
     * @return string
     */
    public static function suffixScenarioFileName(string $name): string
    {
        return $name.self::SCENARIO_FILE_EXTENSION;
    }

    /**
     * @param  string  $fileName
     *
     * @return string
     */
    public static function prefixScenariosPath(string $fileName): string
    {
        return self::getScenariosPath()."/$fileName";
    }

    public static function getScenarioFilePath(string $odId): string
    {
        return self::prefixScenariosPath(self::suffixScenarioFileName($odId));
    }

    /**
     * @param  string  $filePath
     * @param  string  $data
     */
    public static function createScenarioFile(string $filePath, string $data): void
    {
        self::getDisk()->put($filePath, $data);
    }

    /**
     * @param  string  $filePath
     */
    public static function deleteScenarioFile(string $filePath): void
    {
        self::getDisk()->delete($filePath);
    }

    /**
     * @return array|false
     */
    public static function getScenarioFiles()
    {
        $files = self::getDisk()->files(self::getScenariosPath());
        return preg_grep('/^([^.])/', $files);
    }


    /**
     * Read a scenario file
     *
     * @param  string  $filePath
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function getScenarioFileData(string $filePath)
    {
        return self::getDisk()->get($filePath);
    }

    public static function scenarioFileExists(string $filePath)
    {
        return self::getDisk()->exists($filePath);
    }

    public static function getSerializedData(Scenario $scenario): string
    {
        $uidMap = PathSubstitutionHelper::createScenarioMap($scenario);

        $serialized = ImportExportSerializer::serialize($scenario, 'json', [
            ScenarioNormalizer::UID_MAP => $uidMap
        ]);

        $uid = $scenario->getUid();
        if ($uid) {
            /** @var Collection $configurations */
            $configurations = ComponentConfiguration::select([
                'name',
                'scenario_id',
                'component_id',
                'configuration',
                'active',
            ])
                ->where(['scenario_id' => $uid])
                ->orderBy('created_at', 'ASC')
                ->get();

            $configurations->each(function (ComponentConfiguration $c) use ($uidMap) {
                $c->scenario_id = $uidMap->get($c->scenario_id, $c->scenario_id);
            });

            $serializedArray = json_decode($serialized, true);
            $serializedArray[self::CONFIGURATIONS] = $configurations->toArray();
            $serialized = json_encode($serializedArray);
        }

        return $serialized;
    }

    /**
     * @param  string  $data
     *
     * @return Scenario
     */
    public static function importScenarioFromString(string $data): Scenario
    {
        $hasPathsToSubstitute = PathSubstitutionHelper::stringContainsPaths($data);

        $serializerContext = [];

        if ($hasPathsToSubstitute) {
            $serializerContext = [
                ScenarioNormalizer::IGNORE_OBJECTS_WITH_POTENTIAL_PATH_VALUES => true,
            ];
        }

        /* @var $importingScenario Scenario */
        $importingScenario = ImportExportSerializer::deserialize($data, Scenario::class, 'json', $serializerContext);

        $existingScenarios = ConversationDataClient::getAllScenarios();

        $duplicateScenarios = $existingScenarios->filter(
            fn ($scenario) => $scenario->getOdId() === $importingScenario->getOdId()
        );
        if ($duplicateScenarios->count() > 0) {
            throw new DuplicateConversationObjectOdIdException(
                $importingScenario->getOdId(),
                sprintf(
                    "Cannot import scenario with odId %s. A scenario with that odId already exists!",
                    $importingScenario->getOdId()
                )
            );
        }

        $persistedScenario = ScenarioDataClient::addFullScenarioGraph($importingScenario);
        $persistedScenario = ScenarioDataClient::getFullScenarioGraph($persistedScenario->getUid());

        $map = PathSubstitutionHelper::createScenarioMap($persistedScenario);

        if ($hasPathsToSubstitute) {
            // Deserialize WITH objects with potential path values and substitute the paths for the UIDs
            /** @var Scenario $scenarioWithPathsSubstituted */
            $scenarioWithPathsSubstituted = ImportExportSerializer::deserialize($data, Scenario::class, 'json', [
                ScenarioNormalizer::UID_MAP => $map
            ]);
        } else {
            $scenarioWithPathsSubstituted = $persistedScenario;
        }

        $dataArray = json_decode($data, true);
        if (!array_key_exists(ScenarioImportExportHelper::CONFIGURATIONS, $dataArray)) {
            ScenariosController::createDefaultConfigurationsForScenario($scenarioWithPathsSubstituted->getUid());
        } else {
            foreach ($dataArray[ScenarioImportExportHelper::CONFIGURATIONS] as $configurationData) {
                $substitutedScenarioId = $map->get($configurationData['scenario_id'], $configurationData['scenario_id']);
                $configurationData['scenario_id'] = $substitutedScenarioId;
                ComponentConfiguration::create($configurationData);
            }
        }

        return self::patchScenario($persistedScenario, $scenarioWithPathsSubstituted);
    }

    /**
     * @param Scenario $persistedScenario
     * @param Scenario $scenarioWithPathsSubstituted
     * @return Scenario
     */
    public static function patchScenario(Scenario $persistedScenario, Scenario $scenarioWithPathsSubstituted): Scenario
    {
        if (PathSubstitutionHelper::shouldPatch($scenarioWithPathsSubstituted)) {
            $scenarioPatch = PathSubstitutionHelper::createPatch($persistedScenario->getUid(), $scenarioWithPathsSubstituted);
            ConversationDataClient::updateScenario($scenarioPatch);
        }

        foreach ($scenarioWithPathsSubstituted->getConversations() as $cIdx => $conversationWithPathsSubstituted) {
            /** @var Conversation $conversationWithPathsSubstituted */

            /** @var Conversation $persistedConversation */
            $persistedConversation = $persistedScenario->getConversations()[$cIdx];

            self::patchConversation($persistedConversation, $conversationWithPathsSubstituted);
        }

        return ScenarioDataClient::getFullScenarioGraph($persistedScenario->getUid());
    }

    /**
     * @param Conversation $persistedConversation
     * @param Conversation $conversationWithPathsSubstituted
     */
    public static function patchConversation(
        Conversation $persistedConversation,
        Conversation $conversationWithPathsSubstituted
    ): void {
        if (PathSubstitutionHelper::shouldPatch($conversationWithPathsSubstituted)) {
            $conversationPatch = PathSubstitutionHelper::createPatch(
                $persistedConversation->getUid(),
                $conversationWithPathsSubstituted
            );
            ConversationDataClient::updateConversation($conversationPatch);
        }

        foreach ($conversationWithPathsSubstituted->getScenes() as $sIdx => $sceneWithPathsSubstituted) {
            /** @var Scene $sceneWithPathsSubstituted */

            /** @var Scene $persistedScene */
            $persistedScene = $persistedConversation->getScenes()[$sIdx];

            self::patchScene($persistedScene, $sceneWithPathsSubstituted);
        }
    }

    /**
     * @param Scene $persistedScene
     * @param Scene $sceneWithPathsSubstituted
     */
    public static function patchScene(Scene $persistedScene, Scene $sceneWithPathsSubstituted): void
    {
        if (PathSubstitutionHelper::shouldPatch($sceneWithPathsSubstituted)) {
            $scenePatch = PathSubstitutionHelper::createPatch($persistedScene->getUid(), $sceneWithPathsSubstituted);
            ConversationDataClient::updateScene($scenePatch);
        }

        foreach ($sceneWithPathsSubstituted->getTurns() as $tIdx => $turnWithPathsSubstituted) {
            /** @var Turn $turnWithPathsSubstituted */

            /** @var Turn $persistedTurn */
            $persistedTurn = $persistedScene->getTurns()[$tIdx];

            self::patchTurn($persistedTurn, $turnWithPathsSubstituted);
        }
    }

    /**
     * @param Turn $persistedTurn
     * @param Turn $turnWithPathsSubstituted
     */
    public static function patchTurn(Turn $persistedTurn, Turn $turnWithPathsSubstituted): void
    {
        if (PathSubstitutionHelper::shouldPatch($turnWithPathsSubstituted)) {
            $turnPatch = PathSubstitutionHelper::createPatch($persistedTurn->getUid(), $turnWithPathsSubstituted);
            ConversationDataClient::updateTurn($turnPatch);
        }

        foreach ($turnWithPathsSubstituted->getRequestIntents() as $iIdx => $intent) {
            /** @var Intent $intent */

            /** @var Intent $persistedIntent */
            $persistedIntent = $persistedTurn->getRequestIntents()[$iIdx];

            if (PathSubstitutionHelper::shouldPatch($intent)) {
                $intentPatch = PathSubstitutionHelper::createPatch($persistedIntent->getUid(), $intent);
                ConversationDataClient::updateIntent($intentPatch);
            }
        }

        foreach ($turnWithPathsSubstituted->getResponseIntents() as $iIdx => $intent) {
            /** @var Turn $intent */

            /** @var Intent $persistedIntent */
            $persistedIntent = $persistedTurn->getResponseIntents()[$iIdx];

            if (PathSubstitutionHelper::shouldPatch($intent)) {
                $intentPatch = PathSubstitutionHelper::createPatch($persistedIntent->getUid(), $intent);
                ConversationDataClient::updateIntent($intentPatch);
            }
        }
    }
}
