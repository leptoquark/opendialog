<?php

namespace App\Console\Commands\ConfigurationUpdates\Updates;

use App\Console\Commands\ConfigurationUpdates\BaseConfigurationUpdate;
use Exception;
use Illuminate\Support\Facades\DB;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\Core\Components\Configuration\ConfigurationDataHelper;
use OpenDialogAi\Core\Conversation\Facades\ConversationObjectDataClient;
use OpenDialogAi\InterpreterEngine\Interpreters\OpenDialogInterpreter;

class UpdateDefaultCallbackToOpenDialogInterpreter extends BaseConfigurationUpdate
{
    /**
     * @inheritDoc
     */
    public function hasRun(): bool
    {
        $configuration = ComponentConfiguration::where('name', ConfigurationDataHelper::OPENDIALOG_INTERPRETER)
            ->where('scenario_id', '')
            ->first();

        return !is_null($configuration);
    }

    /**
     * @inheritDoc
     */
    public function beforeUp(): bool
    {
        if (!$this->checkInterpreterComponentExists(OpenDialogInterpreter::getComponentId())) {
            return false;
        }

        $this->warn(sprintf(
            "Configuration '%s' is deprecated, please update it to '%s'.",
            ConfigurationDataHelper::DEFAULT_CALLBACK,
            ConfigurationDataHelper::OPENDIALOG_INTERPRETER
        ));

        $updateConfirm = sprintf(
            'Are you sure you want to update the "%s" configuration name to "%s"? This will update the'
            . ' configuration, and all scenarios that use it.',
            ConfigurationDataHelper::DEFAULT_CALLBACK,
            ConfigurationDataHelper::OPENDIALOG_INTERPRETER
        );

        return $this->option('non-interactive') || $this->confirm($updateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        /** @var ComponentConfiguration $original */
        $original = ComponentConfiguration::where(['name' => ConfigurationDataHelper::DEFAULT_CALLBACK])->first();

        try {
            DB::transaction(function () use ($original) {
                ComponentConfiguration::where(['name' => ConfigurationDataHelper::DEFAULT_CALLBACK])->delete();
                $this->createOpenDialogInterpreterForScenario('', $original->configuration);
            });
        } catch (Exception $e) {
            throw new Exception(sprintf('Update failed, configurations were not updated: %s', $e->getMessage()));
        }

        $this->info('Configurations updated.');

        $this->info('Updating configuration usage in scenarios...');

        ConversationObjectDataClient::updateAllConversationObjectInterpreters(
            ConfigurationDataHelper::DEFAULT_CALLBACK,
            ConfigurationDataHelper::OPENDIALOG_INTERPRETER
        );
    }

    /**
     * @inheritDoc
     */
    public function beforeDown(): bool
    {
        $updateConfirm = sprintf(
            'Are you sure you want to update the "%s" configuration name to "%s"? This will update the'
            . ' configuration, and all scenarios that use it.',
            ConfigurationDataHelper::OPENDIALOG_INTERPRETER,
            ConfigurationDataHelper::DEFAULT_CALLBACK
        );

        return $this->option('non-interactive') || $this->confirm($updateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $original = ComponentConfiguration::where(['name' => ConfigurationDataHelper::OPENDIALOG_INTERPRETER])->first();

        try {
            DB::transaction(function () use ($original) {
                ComponentConfiguration::where(['name' => ConfigurationDataHelper::OPENDIALOG_INTERPRETER])->delete();
                $this->createCallbackInterpreterForScenario('', $original->configuration);
            });
        } catch (Exception $e) {
            throw new Exception(sprintf('Update failed, configurations were not updated: %s', $e->getMessage()));
        }

        $this->info('Configurations updated.');

        $this->info('Updating configuration usage in scenarios...');

        ConversationObjectDataClient::updateAllConversationObjectInterpreters(
            ConfigurationDataHelper::OPENDIALOG_INTERPRETER,
            ConfigurationDataHelper::DEFAULT_CALLBACK
        );
    }

    /**
     * @param string $scenarioUid
     * @param array $withConfiguration
     */
    public static function createCallbackInterpreterForScenario(string $scenarioUid, array $withConfiguration = []): void
    {
        ComponentConfiguration::create([
            'name' => ConfigurationDataHelper::DEFAULT_CALLBACK,
            'scenario_id' => $scenarioUid,
            'component_id' => 'interpreter.core.callbackInterpreter',
            'configuration' => $withConfiguration + [
                    'callbacks' => [
                        'WELCOME' => 'intent.core.welcome',
                    ],
                ],
            'active' => true,
        ]);
    }
}
