<?php

namespace App\Console\Commands\ConfigurationUpdates\Updates;

use App\Console\Commands\ConfigurationUpdates\BaseConfigurationUpdate;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\Core\Components\Configuration\ConfigurationDataHelper;

class CreateDefaultCallbackInterpreter extends BaseConfigurationUpdate
{
    /**
     * @inheritDoc
     */
    public function hasRun(): bool
    {
        $configuration = ComponentConfiguration::where('name', ConfigurationDataHelper::DEFAULT_CALLBACK)
            ->where('scenario_id', '')
            ->first();

        return !is_null($configuration);
    }

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->info('Creating Default Callback interpreter');

        ComponentConfiguration::create([
            'name' => ConfigurationDataHelper::DEFAULT_CALLBACK,
            'scenario_id' => '',
            'component_id' => 'interpreter.core.callbackInterpreter',
            'configuration' => [
                'callbacks' => [
                    'WELCOME' => 'intent.core.welcome'
                ]
            ],
            'active' => true,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function beforeDown(): bool
    {
        $updateConfirm = sprintf(
            'Are you sure you want to remove the "%s" configuration?',
            ConfigurationDataHelper::DEFAULT_CALLBACK
        );

        return $this->option('non-interactive') || $this->confirm($updateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->info('Removing Default Callback interpreter');

        ComponentConfiguration::where('name', ConfigurationDataHelper::DEFAULT_CALLBACK)
            ->where('scenario_id', '')
            ->delete();
    }
}
