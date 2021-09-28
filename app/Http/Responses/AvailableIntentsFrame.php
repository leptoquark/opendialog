<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseNode;
use OpenDialogAi\ConversationEngine\Simulator\ConversationSimulator;
use OpenDialogAi\ConversationEngine\Simulator\ConversationSimulatorResponseIntentStatePair;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;
use OpenDialogAi\Core\Conversation\Facades\ScenarioDataClient;
use OpenDialogAi\Core\Conversation\Intent;

abstract class AvailableIntentsFrame extends FrameDataResponse
{
    public string $startEventName;
    public string $endEventName;
    public string $stateEventName;

    public string $speaker = Intent::USER;

    public array $availableIntents = [];

    public string $name = "Considered Path";

    /**
     * Override this method, as we want to run the conversational state through the simulator
     */
    protected function setNodes(): void
    {
        // If this is an initial request, we will not have a scenario ID - take one from the ScenarioRequestReceived event
        if ($this->stateEvent->getScenarioId() === 'undefined') {
            $this->stateEvent->setScenarioId($this->getScenarioIdFromEvent(ScenarioRequestReceived::class));
        }

        $this->addScenario(ScenarioDataClient::getFullScenarioGraph($this->stateEvent->getScenarioId()));

        $conversationalState = $this->stateEvent->convertToConversationalState($this->speaker);
        ConversationSimulator::simulate($conversationalState)->getIntentStatePairs()
            ->each(function (ConversationSimulatorResponseIntentStatePair $intentState) {
                $this->availableIntents[] = $intentState->getIntent()->getName();

                $this->setNodeStatus($intentState->getIntent()->getScenario()->getUid(), BaseNode::CONSIDERED);
                $this->setNodeStatus($intentState->getIntent()->getConversation()->getUid(), BaseNode::CONSIDERED);
                $this->setNodeStatus($intentState->getIntent()->getScene()->getUid(), BaseNode::CONSIDERED);
                $this->setNodeStatus($intentState->getIntent()->getTurn()->getUid(), BaseNode::CONSIDERED);
                $this->setNodeStatus($intentState->getIntent()->getUid(), BaseNode::CONSIDERED);
            });
    }

    /**
     * @inheritDoc
     */
    public function annotate(): void
    {
        $this->annotations[self::AVAILABLE_INTENTS] = $this->availableIntents;
    }
}
