<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseNode;
use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Events\Interpretation\CouldNotInterpret;
use OpenDialogAi\Core\Conversation\Events\Interpretation\FewerExtractedAttributes;
use OpenDialogAi\Core\Conversation\Events\Interpretation\InterpretationFailed;
use OpenDialogAi\Core\Conversation\Events\Interpretation\LowerConfidenceLevel;
use OpenDialogAi\Core\Conversation\Events\Operations\ConditionsFailed;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;
use OpenDialogAi\Core\Conversation\Facades\ScenarioDataClient;

abstract class SelectionFrame extends FrameDataResponse
{
    public string $startEventName;
    public string $endEventName;
    public string $stateEventName;

    public string $selectedIntentEvent;

    public array $rejectionEvents = [
        LowerConfidenceLevel::class,
        FewerExtractedAttributes::class,
        ConditionsFailed::class,
        InterpretationFailed::class,
        CouldNotInterpret::class
    ];

    public string $name = "Selected Path";

    public string $selectedIntentId;

    /**
     * @inheritDoc
     */
    protected function setNodes(): void
    {
        $scenarioId = $this->stateEvent->getScenarioId();
        $this->addScenario(ScenarioDataClient::getFullScenarioGraph($scenarioId));

        $this->setNodeStatus($this->stateEvent->getScenarioId(), BaseNode::CONSIDERED);

        $this->setNodeStatus($this->stateEvent->getConversationId(), BaseNode::CONSIDERED);

        $this->setNodeStatus($this->stateEvent->getSceneId(), BaseNode::CONSIDERED);

        $this->setNodeStatus($this->stateEvent->getTurnId(), BaseNode::CONSIDERED);
    }

    public function annotate(): void
    {
        // Find the matched intent from the events
        /** @var StoredEvent $matchedIntent */
        $matchedIntent = $this->getEvents($this->selectedIntentEvent)->first();
        if ($matchedIntent) {
            $intentId = $matchedIntent->getObjectId();
            $intentName = $matchedIntent->getObjectName();
            $this->selectedIntentId = $intentId;

            $events = $this->getAllEventsForObject($intentId);
            $data = $events
                ->unique(fn ($event) => $event->getMessage()) // Avoid duplicated messages
                ->filter(fn ($event) => !is_null($event->getSubject())) // Only events that have subjects
                ->map(fn ($event) => $this->extractData($event))->values();

            $this->addAnnotation($intentId, $intentName, 'intent', $data);
            $this->setNodeStatus($intentId, BaseNode::SELECTED);
        }

        // Find all failure events and annotate
        $this->events->whereIn('event_class', $this->rejectionEvents)
            ->unique(fn ($event) => $event->getObjectId()) // only 1 rejection event per node
            ->filter(fn (StoredEvent $event) => !is_null($event->getSubject())) // only events with subjects
            ->filter(fn (StoredEvent $event) => $event->getObjectId() !== $this->selectedIntentId) // No events for the selected
            ->filter(fn (StoredEvent $event) => $event->getObjectType() !== 'scenario') // No Scenario level events
            ->each(fn ($event) => $this->addRejectionEvent($event));
    }

    protected function addRejectionEvent(StoredEvent $event)
    {
        $objectId = $event->getObjectId();
        $objectName = $event->getObjectName();
        $type = $event->getObjectType();

        $events = $this->getAllEventsForObject($objectId);
        $data = $events
            ->unique(fn ($event) => $event->getMessage()) // avoid duplicate messages
            ->filter(fn ($event) => !is_null($event->getSubject())) // Only events that have subjects
            ->map(fn ($event) => $this->extractData($event))->values();

        $this->addAnnotation($objectId, $objectName, $type, $data, self::REJECTED);
        $this->setNodeStatus($objectId, BaseNode::NOT_SELECTED);
    }

    protected function addAnnotation($objectId, $objectName, $type, $data, $level = self::SELECTED)
    {
        if (!isset($this->annotations[$level])) {
            $this->annotations[$level] = [];
        }

        $this->annotations[$level][] = [
            'label' => $objectName,
            'id' => $objectId,
            'type' => $type,
            'data' => $this->condenseData($data)
        ];
    }

    protected function extractData(StoredEvent $event): array
    {
        return [
            'label' => ucwords($event->getSubject()),
            'message' => [
                'success' => $event->getStatus() !== 'error',
                'message' => $event->getMessage()
            ]
        ];
    }

    protected function condenseData($data): array
    {
        $condensed = [];

        foreach ($data as $event) {
            if (!isset($condensed[$event['label']])) {
                $condensed[$event['label']] = [
                    'label' => $event['label'],
                    'messages' => []
                ];
            }

            $condensed[$event['label']]['messages'][] = $event['message'];
        }

        return $condensed;
    }

    private function getAllEventsForObject($objectId): Collection
    {
        return $this->events->filter(fn ($event) => $event->getObjectId() == $objectId);
    }
}
