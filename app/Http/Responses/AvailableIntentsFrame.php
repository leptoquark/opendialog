<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseData;
use OpenDialogAi\ConversationEngine\Simulator\ConversationSimulator;
use OpenDialogAi\ConversationEngine\Simulator\ConversationSimulatorResponseIntentStatePair;
use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Interpretation\InterpretingIncomingUtteranceForIntent;
use OpenDialogAi\Core\Conversation\Events\Sensor\RequestReceived;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;
use OpenDialogAi\Core\Conversation\Facades\ScenarioDataClient;
use OpenDialogAi\Core\Conversation\Intent;

abstract class AvailableIntentsFrame extends FrameDataResponse
{
    public string $startEventName;
    public string $endEventName;
    public string $stateEventName;

    public string $speaker = Intent::USER;

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
                $this->setNodeStatus($intentState->getIntent()->getScenario()->getUid(), BaseData::CONSIDERED);
                $this->setNodeStatus($intentState->getIntent()->getConversation()->getUid(), BaseData::CONSIDERED);
                $this->setNodeStatus($intentState->getIntent()->getScene()->getUid(), BaseData::CONSIDERED);
                $this->setNodeStatus($intentState->getIntent()->getTurn()->getUid(), BaseData::CONSIDERED);
                $this->setNodeStatus($intentState->getIntent()->getUid(), BaseData::CONSIDERED);
            });
    }
}
