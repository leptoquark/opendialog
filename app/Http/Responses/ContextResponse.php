<?php

namespace App\Http\Responses;


use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;
use OpenDialogAi\Core\Conversation\Facades\IntentDataClient;
use OpenDialogAi\Core\Conversation\Intent;

class ContextResponse
{
    public StoredEvent $contextEvent;
    public Intent $intent;

    /**
     * Add the context event that contains the intent ID of the selected incoming / outgoing intent
     * The intent data will be fetched from the graph to be able to generate the response when needed
     *
     * @param StoredEvent $event
     * @return $this
     */
    public function setContextEvent(StoredEvent $event): ContextResponse
    {
        $this->contextEvent = $event;
        $this->intent = IntentDataClient::getFullIntentGraph($event->getObjectId());
        return $this;
    }

    public function generateResponse(): array
    {
        return [
            [
                'type' => 'scenario',
                'name' => $this->intent->getScenario()->getName(),
                'id' => $this->intent->getScenario()->getUid()
            ],
            [
                'type' => 'conversation',
                'name' => $this->intent->getConversation()->getName(),
                'id' => $this->intent->getConversation()->getUid()
            ],
            [
                'type' => 'scene',
                'name' => $this->intent->getScene()->getName(),
                'id' => $this->intent->getScene()->getUid()
            ],
            [
                'type' => 'turn',
                'name' => $this->intent->getTurn()->getName(),
                'id' => $this->intent->getTurn()->getUid()
            ],
            [
                'type' => 'intent',
                'name' => $this->intent->getName(),
                'id' => $this->intent->getUid()
            ]
        ];
    }
}
