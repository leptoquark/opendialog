<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseNode;
use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;
use OpenDialogAi\Core\Conversation\Scenario;

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
    public array $annotations = [];

    /** @var string The name of the first relevant event to the frame */
    public string $startEventName;

    /** @var string The name of the final relevant event to the frame */
    public string $endEventName;

    /** @var string The name of the event that holds state data */
    public string $stateEventName;

    /** @var StoredEvent The actual event */
    public StoredEvent $stateEvent;

    public string $name = "";

    public const TRANSITION        = "Transition";
    public const AVAILABLE_INTENTS = "Available Intents";
    public const SELECTED          = 'Selected';
    public const REJECTED          = 'Rejected';

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
        $this->setStartPoint();
        $this->annotate();
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
     *
     * @return void
     */
    protected abstract function setNodes(): void;

    /**
     * Loop through the events relevant to this frame and compile the annotation data
     *
     * @return void
     */
    public abstract function annotate(): void;

    /**
     * @param Scenario $scenario
     */
    public function addScenario(Scenario $scenario)
    {
        $this->nodes = $this->nodes->concat(BaseNode::generateConversationNodesFromScenario($scenario));
    }

    /**
     * @param $events
     * @return FrameDataResponse
     */
    public function addEvents($events): FrameDataResponse
    {
        $this->events = new Collection($events);
        return $this;
    }

    /**
     * Sets the status on a node with the given ID if the node exists.
     * Will not downgrade a node from selected if it already has that status
     *
     * @param $nodeId
     * @param $status
     */
    public function setNodeStatus($nodeId, $status)
    {
        $node = $this->getNode($nodeId);

        if ($node) {
            // Don't downgrade a nodes selected status
            if ($node->status === BaseNode::SELECTED) {
                return;
            }

            $node->status = $status;
            if ($status !== BaseNode::NOT_CONSIDERED) {
                $node->shouldDraw = true;
            }

            // Only draw up the tree if this is not a starting state node and there is a parent
            if (!$node->startingState && $node->parentId) {
                $this->setNodeStatus($node->parentId, $status);
            }
        }
    }

    /**
     * Sets the start point on a node based on the $stateEvent
     */
    public function setStartPoint(): void
    {
        if ($this->stateEvent->getTurnId() !== 'undefined' && $this->stateEvent->getTurnStatus() !== 'OUT_OF_TURN') {
            $this->setNodeAsStartPoint($this->stateEvent->getTurnId());
        } else if ($this->stateEvent->getSceneId() !== 'undefined') {
            $this->setNodeAsStartPoint($this->stateEvent->getSceneId());
        } else if ($this->stateEvent->getConversationId() !== 'undefined') {
            $this->setNodeAsStartPoint($this->stateEvent->getConversationId());
        } else if ($this->stateEvent->getScenarioId() !== 'undefined') {
            $this->setNodeAsStartPoint($this->stateEvent->getScenarioId());
        }
    }

    /**
     * Sets the node with given ID as the starting point if it exists
     *
     * @param $nodeId
     */
    public function setNodeAsStartPoint($nodeId)
    {
        $node = $this->getNode($nodeId);

        if ($node) {
            $node->startingState = true;
        }
    }

    /**
     * Adds each node and its connections if it should be drawn
     *
     * @return array
     */
    private function formatResponse(): array
    {
        // Expand should draw to show siblings for clarity
        $parentIds = $this->nodes
            ->where('shouldDraw', true)
            ->whereNotNull('parentId')
            ->map(fn ($node) => $node->parentId)
            ->unique();

        $this->nodes
            ->whereIn('parentId', $parentIds->values())
            ->each(fn ($node) => $node->shouldDraw = true);

        $this->nodes->each(function (BaseNode $node) {
            if ($node->shouldDraw) {
                $this->frameData[] = ['data' => $node->toArray()];
            }
        });

        $this->nodes->whereNotNull('parentId')->each(function (BaseNode $node) {
            if ($node->shouldDraw) {
                $this->connections[] = $node->generateConnection($this->getNode($node->parentId));
            }
        });

        return [
            'name' => $this->name,
            'nodes' => array_merge($this->frameData, $this->connections),
            'data' => $this->annotations,
            'events' => $this->events->map(fn (StoredEvent $event) => $event->getEventClass())
        ];
    }

    /**
     * @param $id
     * @return BaseNode
     */
    protected function getNode($id): ?BaseNode
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

    protected function getScenarioIdFromEvent($eventClass)
    {
        return $this->getEventProperty($eventClass, 'scenarioId');
    }
}
