<?php

namespace App\Console\Commands\ConfigurationUpdates\Updates;

use App\Console\Commands\ConfigurationUpdates\BaseConfigurationUpdate;
use Exception;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\Core\Components\Configuration\ConfigurationDataHelper;
use OpenDialogAi\Core\Conversation\Facades\ConversationDataClient;
use OpenDialogAi\Core\Conversation\Scenario;
use OpenDialogAi\PlatformEngine\Components\WebchatPlatform;
use OpenDialogAi\Webchat\WebchatSetting;

class CreateWebchatPlatforms extends BaseConfigurationUpdate
{
    /**
     * @inheritDoc
     */
    public function hasRun(): bool
    {
        $configuration = ComponentConfiguration::where('name', ConfigurationDataHelper::WEBCHAT_PLATFORM)
            ->where('component_id', WebchatPlatform::getComponentId())
            ->first();

        return !is_null($configuration);
    }

    /**
     * @inheritDoc
     */
    public function beforeUp(): bool
    {
        if (!$this->checkConfigurationsAreScopedByScenario()) {
            return false;
        }

        $this->warn("Webchat settings are deprecated, they should now be stored as platform configuration"
            . " for each scenario. Please convert your webchat settings.");

        $updateConfirm = 'Are you sure you want to convert webchat settings to platform configuration?';

        return $this->option('non-interactive') || $this->confirm($updateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->createWebchatPlatformForEachScenario();
    }

    /**
     * @inheritDoc
     */
    public function beforeDown(): bool
    {
        $updateConfirm = 'Are you sure you want to convert platform configuration back to webchat settings?';

        return $this->option('non-interactive') || $this->confirm($updateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        ComponentConfiguration::where([
            'name' => ConfigurationDataHelper::WEBCHAT_PLATFORM,
        ])->delete();
    }

    public function createWebchatPlatformForEachScenario(): void
    {
        ConversationDataClient::getAllScenarios()->each(function (Scenario $scenario) {
            if (self::scenarioHasConfiguration($scenario->getUid(), ConfigurationDataHelper::WEBCHAT_PLATFORM)) {
                $this->info(sprintf(
                    "Skipping: Configuration '%s' already exists for the '%s' component on the '%s' scenario.",
                    ConfigurationDataHelper::WEBCHAT_PLATFORM,
                    WebchatPlatform::getComponentId(),
                    $scenario->getName()
                ));
            } else {
                self::createWebchatPlatformForScenario($scenario->getUid(), $this->convertSettingsToArray());

                $this->info(sprintf(
                    "Configuration '%s' was created for the '%s' component on the '%s' scenario.",
                    ConfigurationDataHelper::WEBCHAT_PLATFORM,
                    WebchatPlatform::getComponentId(),
                    $scenario->getName()
                ));
            }
        });
    }

    /**
     * @param string $scenarioUid
     * @param array $configuration
     */
    public static function createWebchatPlatformForScenario(string $scenarioUid, array $configuration): void
    {
        ComponentConfiguration::create([
            'name' => ConfigurationDataHelper::WEBCHAT_PLATFORM,
            'scenario_id' => $scenarioUid,
            'component_id' => WebchatPlatform::getComponentId(),
            'configuration' => $configuration,
            'active' => true,
        ]);
    }

    /**
     * @throws Exception
     */
    public function checkConfigurationsAreScopedByScenario(): bool
    {
        $validConfiguration = ComponentConfiguration::where(['name' => ConfigurationDataHelper::OPENDIALOG_INTERPRETER])
            ->where('scenario_id', '<>', '')
            ->first();

        if (is_null($validConfiguration)) {
            throw new Exception("Check unsuccessful: Configurations aren't all scoped by scenario,"
                . " please ensure you have run all necessary updates");
        } else {
            $this->info("Check successful: Configurations are all scoped by scenario.");
            return true;
        }
    }

    private function convertSettingsToArray(): array
    {
        // Create the config object.
        $config = [];

        // First, get all child settings.
        $parentIds = [];
        $childSettings = WebchatSetting::whereNotNull('parent_id')->get();
        foreach ($childSettings as $childSetting) {
            if (!in_array($childSetting->parent_id, $parentIds)) {
                $parentIds[] = $childSetting->parent_id;
            }
        }

        // Next, get all top level settings.
        $settings = WebchatSetting::whereNull('parent_id')->get();

        // Build the config array.
        foreach ($settings as $setting) {
            if (!in_array($setting->id, $parentIds) && !is_null($setting->value)) {
                $value = $this->castValue($setting->type, $setting->value);
                $config[$setting->name] = $value;
            } else {
                foreach ($childSettings as $idx => $childSetting) {
                    if (($childSetting->parent_id == $setting->id) && !is_null($childSetting->value)) {
                        $value = $this->castValue($childSetting->type, $childSetting->value);
                        $config[$setting->name][$childSetting->name] = $value;
                        unset($childSettings[$idx]);
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Handle the incoming request.
     *
     * @param string $type
     * @param string $value
     * @return mixed
     */
    private function castValue($type, $value)
    {
        switch ($type) {
            case 'number':
                $value = (int)$value;
                break;
            case 'boolean':
                $value = boolval($value);
                break;
            case 'map':
            case 'object':
                $value = json_decode($value);
                break;
            default:
                break;
        }

        return $value;
    }
}
