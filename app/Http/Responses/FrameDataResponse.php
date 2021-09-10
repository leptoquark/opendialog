<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseData;
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
     * @var Collection Events for the frame. They will be run through filterEvents()
     */
    public Collection $events;

    public int $totalFrames;
    public array $frameData = [];
    public array $connections = [];

    public function __construct()
    {
        $this->nodes = new Collection();
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

    public function annotateNode($nodeId, array $annotation)
    {
        $key = array_keys($annotation) ? array_keys($annotation)[0] : null;
        $value  = array_values($annotation) ? array_values($annotation)[0] : $annotation;
        $node = $this->getNode($nodeId);

        if ($key && isset($node->data[$key])) {
            if (is_array($node->data[$key])) {
                $mergeValue = is_array($value) ? $value : [$value];
                $node->data[$key] = array_merge($mergeValue, $node->data[$key]);
            } else {
                $node->data[$key] = [$value, $node->data[$key]];
            }
        } else {
            $node->data[$key] = $value;
        }
    }

    public function setNodeStatus($nodeId, $status)
    {
        $this->getNode($nodeId)->status = $status;
    }

    public function generateResponse(): array
    {
        $this->filterEvents();
        $this->annotateNodes();
        return $this->formatResponse();
    }

    protected abstract function filterEvents(): void;

    protected abstract function annotateNodes(): void;

    private function formatResponse(): array
    {
        $this->nodes->each(function (BaseData $node) {
            $this->frameData[] = ['data' => $node->toArray()];
        });

        $this->nodes->whereNotNull('parentId')->each(function (BaseData $node) {
            $this->connections[] = $node->generateConnection();
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
}
