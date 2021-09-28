<?php

namespace App\Http\Responses;

use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentMatched;
use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;

class IncomingSelectionFrame extends FrameDataResponse
{
    public string $startEventName     = MatchingIncomingIntent::class;
    public string $endEventName       = IncomingIntentMatched::class;
    public string $stateEventName = ScenarioRequestReceived::class;

    public function annotate(): void
    {
        // TODO: Implement annotate() method.
    }
}
