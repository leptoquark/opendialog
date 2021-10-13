<?php

namespace App\Http\Responses;

use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Interpretation\InterpretingIncomingUtteranceForIntent;
use OpenDialogAi\Core\Conversation\Events\Sensor\RequestReceived;
use OpenDialogAi\Core\Conversation\Intent;

class IncomingAvailableIntentsFrame extends AvailableIntentsFrame
{
    public string $startEventName = RequestReceived::class;
    public string $endEventName = InterpretingIncomingUtteranceForIntent::class;
    public string $stateEventName = MatchingIncomingIntent::class;

    public string $speaker = Intent::APP;
}
