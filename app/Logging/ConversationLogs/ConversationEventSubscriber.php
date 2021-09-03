<?php

namespace App\Logging\ConversationLogs;

use OpenDialogAi\Core\Conversation\Events\Interpretation\InterpretedUsingCache;
use OpenDialogAi\Core\Conversation\Events\Interpretation\InterpretingIncomingUtteranceForIntent;
use OpenDialogAi\Core\Conversation\Events\Interpretation\MatchedIntent;
use OpenDialogAi\Core\Conversation\Events\Interpretation\SelectedIntent;
use OpenDialogAi\Core\Conversation\Events\Interpretation\SuccessfulInterpreteration;
use OpenDialogAi\Core\Conversation\Events\Messages\SelectingMessage;
use OpenDialogAi\Core\Conversation\Events\Messages\SelectedMessage;

/**
 * Listens to all relevant events and generates conversation logs on the back of them
 */
class ConversationEventSubscriber
{
    public $subscribes = [
        InterpretingIncomingUtteranceForIntent::class,
        SuccessfulInterpreteration::class,
        InterpretedUsingCache::class,
        MatchedIntent::class,
        SelectedIntent::class,
        SelectingMessage::class,
        SelectedMessage::class
    ];

    public function handleEvent($event)
    {
        resolve(ConversationLogs::class)->addMessage($event);
    }

    /**
     * Register the listeners for the subscriber
     *
     * @param $events
     * @return void
     */
    public function subscribe($events)
    {
        foreach ($this->subscribes as $subscribe) {
            $events->listen(
                $subscribe,
                [ConversationEventSubscriber::class, 'handleEvent']
            );
        }
    }
}