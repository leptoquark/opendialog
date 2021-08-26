<?php

namespace App\Console\Commands\ConfigurationUpdates;

use Exception;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;
use OpenDialogAi\Core\Components\Configuration\ConfigurationDataHelper;
use OpenDialogAi\Core\InterpreterEngine\OpenDialog\OpenDialogInterpreterConfiguration;
use OpenDialogAi\InterpreterEngine\Interpreters\OpenDialogInterpreter;
use OpenDialogAi\InterpreterEngine\Service\InterpreterComponentServiceInterface;
use Symfony\Component\Console\Input\InputInterface;

abstract class BaseConfigurationUpdate implements ConfigurationUpdate
{
    use InteractsWithIO;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputStyle
     */
    protected $output;

    public function __construct(InputInterface $input, OutputStyle $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Runs before the up method, if it returns false (or throws an exception) the updates won't run.
     *
     * @return bool
     * @throws Exception
     */
    public function beforeUp(): bool
    {
        return true;
    }

    /**
     * Runs before the down method, if it returns false (or throws an exception) the updates won't run.
     *
     * @return bool
     * @throws Exception
     */
    public function beforeDown(): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    protected function checkInterpreterComponentExists(string $componentId): bool
    {
        $componentService = resolve(InterpreterComponentServiceInterface::class);

        if ($componentService->has($componentId)) {
            $this->info(sprintf(
                "Check successful: component '%s' is registered.",
                $componentId
            ));
            return true;
        } else {
            throw new Exception(sprintf(
                "Check unsuccessful: component '%s' is not registered.",
                $componentId
            ));
        }
    }

    /**
     * @param string $scenarioUid
     * @param array $withConfiguration
     */
    public static function createOpenDialogInterpreterForScenario(string $scenarioUid, array $withConfiguration = []): void
    {
        ComponentConfiguration::create([
            'name' => ConfigurationDataHelper::OPENDIALOG_INTERPRETER,
            'scenario_id' => $scenarioUid,
            'component_id' => OpenDialogInterpreter::getComponentId(),
            'configuration' => $withConfiguration + [
                    OpenDialogInterpreterConfiguration::ENABLE_SIMILARITY_EVALUATION => true,
                    OpenDialogInterpreterConfiguration::CALLBACKS => [
                        'WELCOME' => 'intent.core.welcome',
                    ],
                ],
            'active' => true,
        ]);
    }

    public static function scenarioHasConfiguration(string $scenarioUid, string $name): bool
    {
        return !is_null(ComponentConfiguration::where(['scenario_id' => $scenarioUid, 'name' => $name])->first());
    }
}
