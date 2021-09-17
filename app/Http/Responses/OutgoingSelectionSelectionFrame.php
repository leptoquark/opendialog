<?php

namespace App\Http\Responses;

use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentMatched;
use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentStateUpdate;
use OpenDialogAi\Core\Conversation\Events\Messages\SelectedMessage;

class OutgoingSelectionSelectionFrame extends IncomingSelectionFrame
{
    public string $startEvent     = IncomingIntentMatched::class;
    public string $endEvent       = SelectedMessage::class;
    public string $stateEventName = IncomingIntentStateUpdate::class;
}
