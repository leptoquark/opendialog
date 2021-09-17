<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseData;
use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Events\BaseConversationEvent;
use OpenDialogAi\Core\Conversation\Events\Operations\ConditionsFailed;
use OpenDialogAi\Core\Conversation\Events\Operations\ConditionsPassed;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;
use OpenDialogAi\Core\Conversation\Facades\ScenarioDataClient;
use OpenDialogAi\Core\Conversation\Scenario;
use OpenDialogAi\Core\Conversation\ScenarioCollection;

/**
 * This is a base class that frame response classes should extend.
 * It deals with formatting the response and has a bunch of helper methods to annotate and set statuses on nodes
 */
abstract class FrameDataResponse
{
    /**
     * @var Collection The nodes relevant to the frame
     */
    public Collection $nodes;

    /**
     * @var Collection Events for the frame. They will be run through filterEvents()
     */
    public Collection $events;

    public int $totalFrames;
    public array $frameData = [];
    public array $connections = [];

    public string $startEvent;
    public string $endEvent;

    public string $stateEventName;
    public StoredEvent $stateEvent;

    public function __construct()
    {
        $this->nodes = new Collection();
    }

    /**
     * Generates a response by filtering the set of events, setting the conversation nodes post filtering, annotating
     * nodes with event data and then formatting
     *
     * @return array
     */
    public function generateResponse(): array
    {
        $this->filterEvents();
        $this->setNodes();
        $this->annotateNodes();
        return $this->formatResponse();
    }

    /**
     * Filters the list of all events by getting all events between the defined start and end events
     */
    protected function filterEvents()
    {
        $this->stateEvent = $this->getEvents($this->stateEventName)->first();

        $startEventReached = false;
        $finalEventReached = false;

        $this->events = $this->events->filter(function ($event) use (&$startEventReached, &$finalEventReached) {
            if ($event->event_class === $this->startEvent) {
                $startEventReached = true;
                return true;
            }

            if ($event->event_class === $this->endEvent) {
                $finalEventReached = true;
                return true;
            }

            return $startEventReached && !$finalEventReached;
        });
    }

    /**
     * After filtering, fetch and set the conversation nodes at the right level for the frame
     */
    protected function setNodes(): void
    {
        $scenarioId = $this->stateEvent->getScenarioId();
        $this->addScenario(ScenarioDataClient::getFullScenarioGraph($scenarioId));

        $this->setNodeStatus($this->stateEvent->getScenarioId(), BaseData::CONSIDERED);
        $this->annotateNode($this->stateEvent->getScenarioId(), 'message', 'Starting State');

        $this->setNodeStatus($this->stateEvent->getConversationId(), BaseData::CONSIDERED);
        $this->annotateNode($this->stateEvent->getConversationId(), 'message', 'Starting State');

        $this->setNodeStatus($this->stateEvent->getSceneId(), BaseData::CONSIDERED);
        $this->annotateNode($this->stateEvent->getSceneId(), 'message', 'Starting State');

        $this->setNodeStatus($this->stateEvent->getTurnId(), BaseData::CONSIDERED);
        $this->annotateNode($this->stateEvent->getTurnId(), 'message', 'Starting State');
    }

    /**
     * With the set of relevant filtered events and conversation nodes, annotate the nodes to represent the event data
     */
    protected function annotateNodes(): void
    {
        /** @var StoredEvent $event */
        foreach ($this->events as $event) {
            $nodeId = $event->getObjectId();
            $nodeStatus = $event->getStatus();
            $nodeType = $event->getObjectType();

            // Add the message to the node
            $this->annotateNode($nodeId, 'message', $event->meta_data['message']);

            // Set the node status
            $status = BaseData::CONSIDERED;
            if ($nodeStatus === 'success') {
                $status = BaseData::SELECTED;
            } else if ($nodeStatus === 'error') {
                $status = BaseData::NOT_SELECTED;
            }

            $this->setNodeStatus($nodeId, $status);

            if ($event->event_class === ConditionsPassed::class) {
                $this->annotateNode($nodeId, 'passingConditions', true);
            }

            if ($event->event_class === ConditionsFailed::class) {
                $this->annotateNode($nodeId, 'passingConditions', false);
            }
        }
    }

    /**
     * @param ScenarioCollection $scenarios
     */
    public function addScenarios(ScenarioCollection $scenarios)
    {
        $scenarios->each(function (Scenario $scenario) {
            $this->addScenario($scenario);
        });
    }

    /**
     * @param Scenario $scenario
     */
    public function addScenario(Scenario $scenario)
    {
        $this->nodes = $this->nodes->concat(BaseData::generateConversationNodesFromScenario($scenario));
    }

    /**
     * @param $events
     */
    public function addEvents($events)
    {
        $this->events = new Collection($events);
    }

    public function annotateNode($nodeId, string $key, string $annotation)
    {
        $node = $this->getNode($nodeId);
        if (!$node) {
            return;
        }

        if (!isset($node->data[$key])) {
            $node->data[$key] = [];
        }

        if (!in_array($annotation, ($node->data[$key]))) {
            // Only annotate if the same message doesn't already exist
            $node->data[$key][] = $annotation;
        }
    }

    public function setNodeStatus($nodeId, $status)
    {
        $node = $this->getNode($nodeId);

        if ($node) {
            // Don't downgrade a nodes selected status
            if ($node->status === BaseData::SELECTED) {
                return;
            }

            $node->status = $status;

            if ($node->type === BaseConversationEvent::INTENT && $parent = $this->getNode($node->parentId)) {
                if ($parent->status !== BaseData::SELECTED) {
                    $parent->status = $node->status;
                }
            }
        }
    }

    public function setIntentStatus($nodeId, $status)
    {
        $intent = $this->getNode($nodeId);
        $intent->status = $status;
        if ($parent = $this->getNode($intent->parentId)) {
            if ($parent->status !== BaseData::SELECTED) {
                $parent->status = $intent->status;
            }
        }
    }

    private function formatResponse(): array
    {
        $this->nodes->each(function (BaseData $node) {
            $parent = $this->getNode($node->parentId);
//            if (!$parent || $parent->status !== BaseData::NOT_CONSIDERED) {
                $this->frameData[] = ['data' => $node->toArray()];
//            }
        });

        $this->nodes->whereNotNull('parentId')->each(function (BaseData $node) {
            $parent = $this->getNode($node->parentId);
//            if ($parent && $parent->status !== BaseData::NOT_CONSIDERED) {
                $this->connections[] = $node->generateConnection();
//            }
        });
        return [
            'total_frames' => $this->totalFrames,
            'frames' => array_merge($this->frameData, $this->connections),
            'events' => $this->events
        ];
    }

    /**
     * @param $id
     * @return BaseData
     */
    protected function getNode($id): ?BaseData
    {
        return $this->nodes->where('id', $id)->first();
    }

    /**
     * Returns all events of the given class type
     *
     * @param $eventClass
     * @return Collection
     */
    protected function getEvents($eventClass): Collection
    {
        return $this->events->where('event_class', $eventClass);
    }

    /**
     * Gets the matching event property for the first event of the given class found
     *
     * @param $eventClass
     * @param $property
     * @return mixed|null
     */
    protected function getEventProperty($eventClass, $property)
    {
        $scenarioEvent = $this->getEvents($eventClass)->first();
        return $scenarioEvent ? $scenarioEvent->event_properties[$property] ?? null : null;
    }

    protected function getScenarioIdsFromEvent($eventClass): Collection
    {
        return collect($this->getEventProperty($eventClass, 'scenarioIds'));
    }

    protected function getScenarioIdFromEvent($eventClass)
    {
        return $this->getEventProperty($eventClass, 'scenarioId');
    }

    protected function getTurnIdFromEvent($eventClass)
    {
        return $this->getEventProperty($eventClass, 'turnId');
    }
}
