<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\TransitionNode;
use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentMatched;
use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentStateUpdate;
use OpenDialogAi\Core\Conversation\Events\ConversationalState\IntentTransition;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;
use OpenDialogAi\Core\Conversation\Intent;

class  OutgoingAvailableIntentsFrame extends AvailableIntentsFrame
{
    public string $startEventName = ScenarioRequestReceived::class;
    public string $endEventName = IncomingIntentMatched::class;
    public string $stateEventName = IncomingIntentStateUpdate::class;

    public string $transitionStateEvent = IntentTransition::class;

    public string $speaker = Intent::USER;

    protected function setNodes(): void
    {
        parent::setNodes();

        /** @var StoredEvent $transitionEvent */
        $transitionEvent = $this->getEvents($this->transitionStateEvent)->first();

        if ($transitionEvent) {
            $transitionNode = TransitionNode::fromTransitionEvent($transitionEvent);
            $transitionsTo = $this->getNode($transitionEvent->getTransitionsTo());
            $this->nodes->prepend($transitionNode);

            $this->connections[] = [
                'data' => [
                    'id' => $transitionsTo->id . '-' . $transitionNode->id,
                    'source' => $transitionNode->id,
                    'target' => $transitionsTo->id,
                    'status' => 'transition',
                    'parent' => $transitionNode->id,
                ]
            ];
        }
    }
}
