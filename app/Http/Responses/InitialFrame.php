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

class InitialFrame extends FrameDataResponse
{
    public string $startEvent = RequestReceived::class;
    public string $endEvent = InterpretingIncomingUtteranceForIntent::class;

    public string $stateEventName = MatchingIncomingIntent::class;

    protected function annotateNodes(): void
    {
        $conversationalState = $this->stateEvent->convertToConversationalState(Intent::APP);
        ConversationSimulator::simulate($conversationalState)->getIntentStatePairs()
            ->each(function (ConversationSimulatorResponseIntentStatePair $intentState) {
                $this->setNodeStatus($intentState->getIntent()->getScenario()->getUid(), BaseData::CONSIDERED);

                $this->setNodeStatus($intentState->getIntent()->getConversation()->getUid(), BaseData::CONSIDERED);
                $this->annotateNode($intentState->getIntent()->getConversation()->getUid(), 'message', 'Has correct behavior');

                $this->setNodeStatus($intentState->getIntent()->getScene()->getUid(), BaseData::CONSIDERED);
                $this->annotateNode($intentState->getIntent()->getScene()->getUid(), 'message', 'Has correct behavior');

                $this->setNodeStatus($intentState->getIntent()->getTurn()->getUid(), BaseData::CONSIDERED);
                $this->annotateNode($intentState->getIntent()->getTurn()->getUid(), 'message', 'Has correct behavior');

                $this->setNodeStatus($intentState->getIntent()->getUid(), BaseData::CONSIDERED);
                $this->annotateNode($intentState->getIntent()->getUid(), 'message', 'Possible Intent');
            });
    }

    protected function setNodes(): void
    {
        // If this is an initial request, we will not have a scenario ID - take one from the ScenarioRequestReceived event
        if ($this->stateEvent->getScenarioId()) {
            $this->stateEvent->setScenarioId($this->getScenarioIdFromEvent(ScenarioRequestReceived::class));
        }

        $scenarioId = $this->stateEvent->getScenarioId();

        // ScenarioRequestReceived event will give us all the scenario id from the incoming request
        $this->addScenario(ScenarioDataClient::getFullScenarioGraph($scenarioId));
    }
}
