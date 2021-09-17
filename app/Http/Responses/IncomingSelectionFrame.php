<?php

namespace App\Http\Responses;

use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentMatched;
use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;

class IncomingSelectionFrame extends FrameDataResponse
{
    public string $startEvent     = MatchingIncomingIntent::class;
    public string $endEvent       = IncomingIntentMatched::class;
    public string $stateEventName = ScenarioRequestReceived::class;
}
