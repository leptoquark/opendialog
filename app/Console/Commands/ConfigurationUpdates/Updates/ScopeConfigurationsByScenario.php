<?php

namespace App\Console\Commands\ConfigurationUpdates\Updates;

use App\Console\Commands\ConfigurationUpdates\BaseConfigurationUpdate;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\Core\Conversation\Facades\ConversationDataClient;
use OpenDialogAi\Core\Conversation\Scenario;

class ScopeConfigurationsByScenario extends BaseConfigurationUpdate
{
    /**
     * @inheritDoc
     */
    public function hasRun(): bool
    {
        $configuration = ComponentConfiguration::where('scenario_id', '')
            ->first();

        return is_null($configuration) && ComponentConfiguration::count() > 0;
    }

    public function beforeUp(): bool
    {
        $numOfScenarios = ConversationDataClient::getAllScenarios()->count();
        $numOfConfigurations = ComponentConfiguration::count();

        $this->warn("Configurations are now scenario specific, please duplicate your existing configurations"
            . " for each scenario and delete the outdated non-specific configurations.");

        $duplicateConfirm = sprintf(
            'Are you sure you want to duplicate all %d configurations for all %d scenarios, and then delete'
            . ' the outdated non-specific configurations?',
            $numOfConfigurations,
            $numOfScenarios
        );

        return $this->option('non-interactive') || $this->confirm($duplicateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        try {
            DB::transaction(function () {
                $outdatedConfigurations = ComponentConfiguration::all();

                $this->duplicateConfigurationsForEachScenario($outdatedConfigurations);

                ComponentConfiguration::whereIn('id', $outdatedConfigurations->pluck('id'))->delete();

                $this->info('Deleted outdated non-specific configurations.');
            });
        } catch (Exception $e) {
            throw new Exception(sprintf('Update failed, configurations were not duplicated: %s', $e->getMessage()));
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeDown(): bool
    {
        $numOfScenarios = ConversationDataClient::getAllScenarios()->count();
        $numOfConfigurations = ComponentConfiguration::count();

        $unduplicateConfirm = sprintf(
            'Are you sure you want to unduplicate all %d configurations for all %d scenarios, and then delete'
            . ' the scenario-specific configurations? If any configurations share the same name they will be overwritten.',
            $numOfConfigurations,
            $numOfScenarios
        );

        return $this->option('non-interactive') || $this->confirm($unduplicateConfirm);
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        try {
            DB::transaction(function () {
                $outdatedConfigurations = ComponentConfiguration::all();

                ComponentConfiguration::whereIn('id', $outdatedConfigurations->pluck('id'))->delete();
                $this->info('Deleted scenario-specific configurations.');

                $this->unduplicateConfigurationsForEachScenario($outdatedConfigurations);
            });
        } catch (Exception $e) {
            throw new Exception(sprintf('Update failed, configurations were not unduplicated: %s', $e->getMessage()));
        }
    }


    public function duplicateConfigurationsForEachScenario(Collection $configurations): void
    {
        ConversationDataClient::getAllScenarios()->each(function (Scenario $scenario) use ($configurations) {
            $configurations->each(function (ComponentConfiguration $c) use ($scenario) {
                $duplicate = $c->replicate();
                $duplicate->scenario_id = $scenario->getUid();
                $duplicate->save();
            });

            $this->info(sprintf("Configurations were duplicated for the '%s' scenario.", $scenario->getName()));
        });
    }

    public function unduplicateConfigurationsForEachScenario(Collection $configurations): void
    {
        $configurations->each(function (ComponentConfiguration $c) {
            if (!is_null(ComponentConfiguration::where('name', $c->name)->first())) {
                return;
            }

            $duplicate = $c->replicate();
            $duplicate->scenario_id = '';
            $duplicate->save();
        });

        $this->info("Configurations were unduplicated");
    }
}
