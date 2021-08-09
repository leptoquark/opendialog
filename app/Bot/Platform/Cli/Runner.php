<?php

namespace App\Bot\Platform\Cli;

use OpenDialogAi\Core\Controllers\OpenDialogController;
use OpenDialogAi\ResponseEngine\Service\ResponseEngineServiceInterface;

class Runner
{
    private OpenDialogController $controller;
    private Sensor $sensor;

    /**
     * Runner constructor.
     * @param OpenDialogController $controller
     * @param Sensor $sensor
     */
    public function __construct(OpenDialogController $controller, Sensor $sensor)
    {
        $this->controller = $controller;
        $this->sensor = $sensor;

        resolve(ResponseEngineServiceInterface::class)->registerFormatter(new Formatter, true);
    }

    public function recieve(Request $request)
    {
        $utterance = $this->sensor->interpreter($request);
        $messageWrapper = $this->controller->runConversation($utterance);

        return $messageWrapper->getMessageToPost();
    }
}
