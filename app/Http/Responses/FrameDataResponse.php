<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseData;
use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Events\BaseConversationEvent;
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

    public array $frameData = [];
    public array $connections = [];

    public string $startEventName;
    public string $endEventName;

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
            if ($event->event_class === $this->startEventName) {
                $startEventReached = true;
                return true;
            }

            if ($event->event_class === $this->endEventName) {
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

        $this->setNodeStatus($this->stateEvent->getConversationId(), BaseData::CONSIDERED);

        $this->setNodeStatus($this->stateEvent->getSceneId(), BaseData::CONSIDERED);

        $this->setNodeStatus($this->stateEvent->getTurnId(), BaseData::CONSIDERED);
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
    public function addEvents($events): FrameDataResponse
    {
        $this->events = new Collection($events);
        return $this;
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
            if ($this->shouldDrawNode($node)) {
                $node->shouldDraw = true;
                $this->frameData[] = ['data' => $node->toArray()];
            } else {
                $node->shouldDraw = false;
            }
        });

        $this->nodes->whereNotNull('parentId')->each(function (BaseData $node) {
            if ($node->shouldDraw) {
                $this->connections[] = $node->generateConnection();
            }
        });

        return [
            'nodes' => array_merge($this->frameData, $this->connections),
            'data' => []
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

    /**
     * @param BaseData $node
     * @return bool
     */
    private function shouldDrawNode(BaseData $node): bool
    {
        if ($node->status !==  BaseData::NOT_CONSIDERED) {
            return true;
        }

        if (!$node->parentId) {
            return true;
        }

        return $this->hasAnyConsideredChildren($node);
    }

    private function hasAnyConsideredChildren(BaseData $node)
    {
        $children = $this->nodes->where('parentId', $node->id);

        foreach ($children as $child) {
            if ($child->status !== BaseData::NOT_CONSIDERED) {
                return true;
            }

            return $this->hasAnyConsideredChildren($child);
        }

        return false;
    }
}
