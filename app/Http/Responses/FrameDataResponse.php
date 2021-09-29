<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseNode;
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

    public const AVAILABLE_INTENTS = "Available Intents";
    public const SELECTED = 'Selected';
    public const REJECTED = 'Rejected';

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
        $this->annotate();
        $this->setStartPoint();
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

            if ($node->parentId) {
                $this->setNodeStatus($node->parentId, $status);
            }
        }
    }

    public function setStartPoint()
    {
        if ($this->stateEvent->getTurnId() && $this->stateEvent->getTurnId() !== 'undefined') {
            $this->setNodeAsStartPoint($this->stateEvent->getTurnId());
        } else if ($this->stateEvent->getSceneId() && $this->stateEvent->getSceneId() !== 'undefined') {
            $this->setNodeAsStartPoint($this->stateEvent->getSceneId());
        } else if ($this->stateEvent->getConversationId() && $this->stateEvent->getConversationId() !== 'undefined') {
            $this->setNodeAsStartPoint($this->stateEvent->getConversationId());
        } else if ($this->stateEvent->getScenarioId() && $this->stateEvent->getScenarioId() !== 'undefined') {
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
        $this->nodes->each(function (BaseNode $node) {
            if ($this->shouldDrawNode($node)) {
                $node->shouldDraw = true;
                $this->frameData[] = ['data' => $node->toArray()];
            } else {
                $node->shouldDraw = false;
            }
        });

        $this->nodes->whereNotNull('parentId')->each(function (BaseNode $node) {
            if ($node->shouldDraw) {
                $this->connections[] = $node->generateConnection();
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

    /**
     * A node should be drawn if its status is anything apart from not_conisdered, it doesn't have a parent,  or
     * any of its children have been considered
     *
     * @param BaseNode $node
     * @return bool
     */
    private function shouldDrawNode(BaseNode $node): bool
    {
        if ($node->status !==  BaseNode::NOT_CONSIDERED) {
            return true;
        }

        if (!$node->parentId) {
            return true;
        }

        return $this->hasAnyConsideredChildren($node);
    }

    /**
     * Recursively checks all of a nodes children and returns true if any have a status other that
     * not_considered
     *
     * @param BaseNode $node
     * @return bool
     */
    private function hasAnyConsideredChildren(BaseNode $node): bool
    {
        $children = $this->nodes->where('parentId', $node->id);

        foreach ($children as $child) {
            if ($child->status !== BaseNode::NOT_CONSIDERED) {
                return true;
            }

            return $this->hasAnyConsideredChildren($child);
        }

        return false;
    }
}
