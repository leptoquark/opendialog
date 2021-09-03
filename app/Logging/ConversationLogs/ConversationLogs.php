<?php

namespace App\Logging\ConversationLogs;

use OpenDialogAi\Core\Conversation\Events\BaseConversationEvent;

/**
 * A place to hold all the logs for a single request. These are not persisted
 */
class ConversationLogs
{
    public array $messages = [];

    public function addMessage(BaseConversationEvent $event)
    {
        $this->messages[] = [
            'status' => $event->status,
            'message' => $event->__toString(),
            'context' => $event->context(),
        ];
    }
}