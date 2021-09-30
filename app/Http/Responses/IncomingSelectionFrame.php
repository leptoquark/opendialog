<?php

namespace App\Http\Responses;

use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentMatched;
use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Intent\TopRankedIntent;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;

class IncomingSelectionFrame extends SelectionFrame
{
    public string $startEventName     = MatchingIncomingIntent::class;
    public string $endEventName       = IncomingIntentMatched::class;
    public string $stateEventName     = MatchingIncomingIntent::class;

    public string $selectedIntentEvent = TopRankedIntent::class;
}
