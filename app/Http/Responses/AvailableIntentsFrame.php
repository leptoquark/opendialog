<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseNode;
use Illuminate\Support\Collection;
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

    public Collection $availableIntents;

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

        $this->availableIntents = new Collection();

        $conversationalState = $this->stateEvent->convertToConversationalState($this->speaker);
        ConversationSimulator::simulate($conversationalState)->getIntentStatePairs()
            ->each(function (ConversationSimulatorResponseIntentStatePair $intentState) {
                $this->availableIntents->add($intentState->getIntent());

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
        $availableIntents =
            $this->availableIntents->map(fn(Intent $intent) => [
                'label' => $intent->getName(),
                'type' => 'intent',
                'id' => $intent->getUid(),
                'data' => [
                    [
                        'messages' => [
                            [
                                "success" => true,
                                "message" => "Has the correct behaviour"
                            ],
                            [
                                "success" => true,
                                "message" => sprintf("Participant %s", $intent->getSpeaker())
                            ]
                        ]
                    ]
                ]
            ]);

        $this->annotations[self::AVAILABLE_INTENTS] = $availableIntents;
    }
}
