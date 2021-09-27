<?php

namespace App\Http\Responses;

use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentMatched;
use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentStateUpdate;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;
use OpenDialogAi\Core\Conversation\Intent;

class  OutgoingAvailableIntentsFrame extends AvailableIntentsFrame
{
    public string $startEventName = ScenarioRequestReceived::class;
    public string $endEventName = IncomingIntentMatched::class;
    public string $stateEventName = IncomingIntentStateUpdate::class;

    public string $speaker = Intent::USER;
}
