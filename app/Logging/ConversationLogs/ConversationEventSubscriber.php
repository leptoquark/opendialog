<?php

namespace App\Logging\ConversationLogs;


/**
 * Listens to all relevant events and generates conversation logs on the back of them
 */
class ConversationEventSubscriber
{
    public $subscribes = [
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