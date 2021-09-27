<?php

namespace App\Http\Responses;

use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentMatched;
use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentStateUpdate;
use OpenDialogAi\Core\Conversation\Events\Messages\SelectedMessage;

class OutgoingSelectionFrame extends IncomingSelectionFrame
{
    public string $startEventName     = IncomingIntentMatched::class;
    public string $endEventName       = SelectedMessage::class;
    public string $stateEventName = IncomingIntentStateUpdate::class;
}
