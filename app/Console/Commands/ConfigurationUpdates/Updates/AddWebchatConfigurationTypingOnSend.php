<?php

namespace App\Console\Commands\ConfigurationUpdates\Updates;

use App\Console\Commands\ConfigurationUpdates\BaseConfigurationUpdate;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\Core\Components\Configuration\ConfigurationDataHelper;
use OpenDialogAi\PlatformEngine\Components\WebchatPlatform;
use OpenDialogAi\Webchat\WebchatSetting;

class AddWebchatConfigurationTypingOnSend extends BaseConfigurationUpdate
{
    /**
     * @inheritDoc
     */
    public function hasRun(): bool
    {
        /** @var ComponentConfiguration $configuration */
        $configuration = ComponentConfiguration::where('name', ConfigurationDataHelper::WEBCHAT_PLATFORM)
            ->where('component_id', WebchatPlatform::getComponentId())
            ->first();

        return $configuration &&
            array_key_exists(WebchatSetting::TYPING_INDICATOR_ON_SEND, $configuration->configuration);
    }

    /**
     * @inheritDoc
     */
    public function beforeUp(): bool
    {
        $updateConfirm = 'Are you sure you want to add the default value to webchat configuration for typingIndicatorOnSend?';

        return $this->option('non-interactive') || $this->confirm($updateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        ComponentConfiguration::where('name', ConfigurationDataHelper::WEBCHAT_PLATFORM)
            ->where('component_id', WebchatPlatform::getComponentId())
            ->each(function (ComponentConfiguration $configuration) {
                if (!array_key_exists(WebchatSetting::TYPING_INDICATOR_ON_SEND, $configuration->configuration)) {
                    $this->info(sprintf(
                        "Updating webchat config for scenario %s - adding value for %s",
                        $configuration->scenario_id,
                        WebchatSetting::TYPING_INDICATOR_ON_SEND)
                    );

                    $config = $configuration->configuration;
                    $config[WebchatSetting::TYPING_INDICATOR_ON_SEND] = false;

                    $configuration->configuration = $config;
                    $configuration->save();
                } else {
                    $this->warn(sprintf(
                            "Not updating webchat config for scenario %s - already has value for %s",
                            $configuration->scenario_id,
                            WebchatSetting::TYPING_INDICATOR_ON_SEND)
                    );
                }
            });
    }

    /**
     * @inheritDoc
     */
    public function beforeDown(): bool
    {
        $updateConfirm = 'Are you sure you want to remove the default value from webchat configuration for typingIndicatorOnSend';

        return $this->option('non-interactive') || $this->confirm($updateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        ComponentConfiguration::where('name', ConfigurationDataHelper::WEBCHAT_PLATFORM)
            ->where('component_id', WebchatPlatform::getComponentId())
            ->each(function (ComponentConfiguration $configuration) {
                if (array_key_exists(WebchatSetting::TYPING_INDICATOR_ON_SEND, $configuration->configuration)) {
                    $this->info(sprintf(
                            "Updating webchat config for scenario %s - removing %s",
                            $configuration->scenario_id,
                            WebchatSetting::TYPING_INDICATOR_ON_SEND)
                    );

                    $config = $configuration->configuration;
                    unset($config[WebchatSetting::TYPING_INDICATOR_ON_SEND]);

                    $configuration->configuration = $config;
                    $configuration->save();
                } else {
                    $this->warn(sprintf(
                            "Not updating webchat config for scenario %s - it doesn't have value for %s",
                            $configuration->scenario_id,
                            WebchatSetting::TYPING_INDICATOR_ON_SEND)
                    );
                }
            });
    }
}
