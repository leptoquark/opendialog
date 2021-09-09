<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseData;
use App\Http\Responses\FrameData\ScenarioData;
use Illuminate\Support\Collection;
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
     * @var Collection Events for the frame. They will be run through
     * @see FrameDataResponse::filterEvents()
     */
    public Collection $events;

    public int $totalFrames;
    public array $frameData = [];
    public array $connections = [];

    public function __construct()
    {
        $this->nodes = new Collection();
    }

    public function addScenarios(ScenarioCollection $scenarios)
    {
        $scenarios->each(function (Scenario $scenario) {
            $this->nodes->add(ScenarioData::fromScenario($scenario));
        });
    }

    public function addScenario($scenario)
    {
        $this->nodes->add(ScenarioData::fromScenario($scenario));
    }

    public function addEvents($events)
    {
        $this->events = new Collection($events);
    }

    public function annotateScenario($scenarioId, array $annotation)
    {
        $this->getScenario($scenarioId)->data[array_keys($annotation)[0]] = array_values($annotation)[0];
    }

    public function setScenarioStatus($scenarioId, $status)
    {
        $this->getScenario($scenarioId)->status = $status;
    }

    public function annotateConversation($conversationId, array $annotation)
    {
        $this->getNode($conversationId)->data[array_keys($annotation)[0]] = array_values($annotation)[0];
    }

    public function setConversationStatus($conversationId, $status)
    {
        $this->getNode($conversationId)->status = $status;
    }

    public abstract function generateResponse();

    public function formatResponse(Collection $startingPoint = null): array
    {
        if ($startingPoint) {
            $nodes = $startingPoint;
        } else {
            $nodes = $this->nodes;
        }

        $nodes->each(function (BaseData $node) {
            $this->frameData[] = ['data' => $node->toArray()];

            $node->children->each(function (BaseData $child) use ($node) {
                $this->frameData[] = ['data' => $child->toArray()];
                $this->connections[] = ['data' => $this->generateConnection($node, $child)];

                if ($child->children->isNotEmpty()) {
                    $this->formatResponse($child->children);
                }
            });
        });

        return [
            'total_frames' => $this->totalFrames,
            'frames' => array_merge($this->frameData, $this->connections),
            'events' => $this->events
        ];
    }

    /**
     * Gets the first scenario from the nodes with matching ID
     *
     * @param $scenarioId
     * @return ScenarioData|null
     */
    protected function getScenario($scenarioId): ?ScenarioData
    {
        return $this->nodes->filter(function (BaseData $scenario) use ($scenarioId) {
            return $scenario->id === $scenarioId;
        })->first();
    }

    /**
     * Loops through all nodes to get one with the matching ID.
     *
     * @param $id
     * @param Collection|null $startPoint
     * @return null
     */
    protected function getNode($id, Collection $startPoint = null)
    {
        $nodes = $this->nodes;
        if (!is_null($startPoint)) {
            $nodes = $startPoint;
        }

        // Loop through all same level nodes first
        foreach ($nodes as $node) {
            if ($node->id === $id) {
                return $node;
            }
        }

        // Then loop through all children
        foreach ($nodes as $node) {
            return $this->getNode($id, $node->children);
        }

        // Node can't be found
        return null;
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

    protected function getConversationIdsFromEvent($eventClass): Collection
    {
        return collect($this->getEventProperty($eventClass, 'conversationIds'));
    }

    protected function getConversationIdFromEvent($eventClass)
    {
        return $this->getEventProperty($eventClass, 'conversationId');
    }

    protected function getSceneIdsFromEvent($eventClass): Collection
    {
        return collect($this->getEventProperty($eventClass, 'sceneIds'));
    }

    protected function getSceneIdFromEvent($eventClass)
    {
        return $this->getEventProperty($eventClass, 'sceneId');
    }

    protected function getTurnIdsFromEvent($eventClass): Collection
    {
        return collect($this->getEventProperty($eventClass, 'turnIds'));
    }

    protected function getTurnIdFromEvent($eventClass)
    {
        return $this->getEventProperty($eventClass, 'turnId');
    }

    protected function getIntentIdsFromEvent($eventClass): Collection
    {
        return collect($this->getEventProperty($eventClass, 'intentIds'));
    }

    protected function getIntentIdFromEvent($eventClass)
    {
        return $this->getEventProperty($eventClass, 'intentId');
    }

    /**
     * Returns all events of the given class type
     *
     * @param $eventClass
     * @return Collection
     */
    private function getEvents($eventClass): Collection
    {
        return $this->events->filter(function ($event) use ($eventClass) {
            return $event->event_class === $eventClass;
        });
    }

    /**
     * Generates a node connection array for use in the response
     *
     * @param BaseData $node
     * @param BaseData $child
     * @return array The connect data for response
     */
    private function generateConnection(BaseData $node, BaseData $child): array
    {
        return [
            'id' => $node->id . '-' . $child->id,
            'source' => $node->id,
            'target' => $child->id,
            'status' => $child->status
        ];
    }
}
