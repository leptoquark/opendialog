<?php

namespace App\Console\Commands;

use App\Console\Commands\ConfigurationUpdates\AllUpdatesHaveRunException;
use App\Console\Commands\ConfigurationUpdates\BaseConfigurationUpdate;
use App\Console\Commands\ConfigurationUpdates\Updates\AddWebchatConfigurationTypingOnSend;
use App\Console\Commands\ConfigurationUpdates\Updates\CreateDefaultCallbackInterpreter;
use App\Console\Commands\ConfigurationUpdates\Updates\CreateWebchatPlatforms;
use App\Console\Commands\ConfigurationUpdates\Updates\ScopeConfigurationsByScenario;
use App\Console\Commands\ConfigurationUpdates\Updates\UpdateDefaultCallbackToOpenDialogInterpreter;
use Ds\Map;
use Exception;
use Illuminate\Console\Command;

class CreateCoreConfigurations extends Command
{
    const VALID_DIRECTIONS = [self::UP, self::DOWN];
    const UP = 'up';
    const DOWN = 'down';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configurations:create'
        . ' {direction=up : up|down}'
        . ' {--u|updates=-1 : Number of updates to perform}'
        . ' {--y|non-interactive : Will automatically answer yes to any confirmations}'
        . ' {--url= : The url of the instance}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the default OpenDialog component configurations.';

    const UPDATES = [
        CreateDefaultCallbackInterpreter::class,
        UpdateDefaultCallbackToOpenDialogInterpreter::class,
        ScopeConfigurationsByScenario::class,
        CreateWebchatPlatforms::class,
        AddWebchatConfigurationTypingOnSend::class
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!in_array($this->argument('direction'), self::VALID_DIRECTIONS)) {
            $this->error('The direction argument must be either "up" or "down".');
            return 1;
        }

        if (!is_numeric($this->option('updates'))) {
            $this->error('The updates option must be numeric.');
            return 1;
        }

        $direction = $this->argument('direction');
        $number = intval($this->option('updates'));

        try {
            $updates = self::UPDATES;
            $updateObjects = new Map();

            // Instantiate the updates
            foreach ($updates as $update) {
                $updateObjects->put($update, new $update($this->input, $this->output));
            }

            // Filter out those that haven't run yet
            $updatesThatHaveRun = $updateObjects->filter(fn ($key, BaseConfigurationUpdate $update) => $update->hasRun());

            if ($direction !== self::UP) {
                $updateObjects = $updateObjects->reversed();
            }

            if ($updatesThatHaveRun->isEmpty()) {
                $lastRunUpdateIndex = null;
            } else {
                // Select the latest update that has run
                $lastRunUpdate = $updatesThatHaveRun->last()->key;
                $lastRunUpdateIndex = array_search($lastRunUpdate, $updateObjects->keys()->toArray());
            }

            $totalNumUpdates = count($updates);

            if (is_null($lastRunUpdateIndex)) {
                $lastRunUpdateIndex = -1;
            }

            try {
                list($startIndex, $endIndex) = self::determineStartAndEndIndices(
                    $direction,
                    $lastRunUpdateIndex,
                    $number,
                    $totalNumUpdates
                );
            } catch (AllUpdatesHaveRunException $e) {
                $this->info("There are no further updates to be run.");
                return 0;
            }

            if (!$this->option('non-interactive') && !$this->confirm(sprintf(
                        'Are you sure you want to run %d updates %s?',
                        $endIndex - $startIndex,
                        $direction)
                )) {
                $this->info('Okay, not updating');
                return 0;
            }

            for ($i = $startIndex; $i < $endIndex; $i++) {
                $this->runUpdate($updateObjects, $i, $direction);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * @param string $direction
     * @param int $lastRunUpdateIndex
     * @param int $number
     * @param int $totalNumUpdates
     * @return array
     * @throws AllUpdatesHaveRunException
     */
    public static function determineStartAndEndIndices(
        string $direction,
        int $lastRunUpdateIndex,
        int $number,
        int $totalNumUpdates
    ): array {
        if ($direction === self::UP) {
            // Start from the latest update that hasn't run
            $startIndex = $lastRunUpdateIndex + 1;

            if ($startIndex >= $totalNumUpdates) {
                throw new AllUpdatesHaveRunException();
            }
        } else {
            if ($lastRunUpdateIndex < 0) {
                throw new AllUpdatesHaveRunException();
            }

            // Start from the latest update that ran
            $startIndex = $lastRunUpdateIndex;
        }

        if ($number < 0) {
            // If no number specified, run them all up
            $number = $totalNumUpdates - $startIndex;
        }

        $endIndex = $startIndex + $number;

        if ($endIndex > $totalNumUpdates) {
            // Ensure it's restricted by number of available updates
            $endIndex = $totalNumUpdates;
        }

        return array($startIndex, $endIndex);
    }

    /**
     * @param Map $updateObjects
     * @param int $i
     * @param string $direction
     * @throws Exception
     */
    public function runUpdate(Map $updateObjects, int $i, string $direction): void
    {
        $key = $updateObjects->keys()->get($i);

        /** @var BaseConfigurationUpdate $update */
        $update = $updateObjects->get($key);

        $this->info(sprintf("\nRunning %s: %s", $direction, $key));

        if ($direction === self::UP) {
            if ($update->beforeUp()) {
                $update->up();
            } else {
                throw new Exception('Before check failed, not running update.');
            }
        } else {
            if ($update->beforeDown()) {
                $update->down();
            } else {
                throw new Exception('Before check failed, not running update.');
            }
        }
    }
}
