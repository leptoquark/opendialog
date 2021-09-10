<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseData;
use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Events\Conversation\FilteredConversations;
use OpenDialogAi\Core\Conversation\Events\Conversation\SelectedStartingConversations;
use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Intent\TopRankedIntent;
use OpenDialogAi\Core\Conversation\Events\Interpretation\SuccessfulInterpreteration;
use OpenDialogAi\Core\Conversation\Events\Scenario\FilteredScenarios;
use OpenDialogAi\Core\Conversation\Events\Scene\FilteredScenes;
use OpenDialogAi\Core\Conversation\Events\Scene\SelectedStartingScenes;
use OpenDialogAi\Core\Conversation\Events\Turn\FilteredTurns;
use OpenDialogAi\Core\Conversation\Events\Turn\SelectedSingleTurn;
use OpenDialogAi\Core\Conversation\Events\Turn\SelectedStartingTurns;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class NewUserIncomingFrame extends FrameDataResponse
{
    public $loopNo;

    /**
     * @param $loopNo
     */
    public function __construct($loopNo)
    {
        parent::__construct();
        $this->loopNo = $loopNo;
    }

    protected function annotateNodes(): void
    {
        // Show selected scenarios
        $this->getScenarioIdsFromEvent(FilteredScenarios::class)->each(function ($scenarioId) {
            $this->setNodeStatus($scenarioId, BaseData::SELECTED);
            $this->annotateNode($scenarioId, ['passingConditions' => true]);
        });

        // Annotate conversations
        $selectedConversations = $this->getConversationIdsFromEvent(SelectedStartingConversations::class);
        $filterConversations = $this->getConversationIdsFromEvent(FilteredConversations::class);
        $this->annotateSelectedFilteredNodes($selectedConversations, $filterConversations);

        // Annotate scenes
        $selectedScenes = $this->getSceneIdsFromEvent(SelectedStartingScenes::class);
        $filterScenes = $this->getSceneIdsFromEvent(FilteredScenes::class);
        $this->annotateSelectedFilteredNodes($selectedScenes, $filterScenes);

        // Annotate turns
        $selectedTurns = $this->getTurnIdsFromEvent(SelectedStartingTurns::class);
        $filterTurns = $this->getTurnIdsFromEvent(FilteredTurns::class);
        $this->annotateSelectedFilteredNodes($selectedTurns, $filterTurns);

        // Annotate Intent interpretations
        $this->getEvents(SuccessfulInterpreteration::class)->each(function (EloquentStoredEvent  $event) {
            $this->setNodeStatus($event->event_properties['intentId'], BaseData::CONSIDERED);
            $this->annotateNode($event->event_properties['intentId'], ['interpretation' => $event->meta_data['message']]);
        });

        // Annotate selected intents
        $topIntent = $this->getEvents(TopRankedIntent::class)->first();
        $this->setNodeStatus($topIntent->event_properties['intentId'], BaseData::SELECTED);
        $this->annotateNode($topIntent->event_properties['intentId'], ['interpretation' => $topIntent->meta_data['message']]);
    }

    public function filterEvents(): void
    {
        $startEventReached = false;
        $finalEventReached = false;

        $this->events = $this->events->filter(function ($event) use (&$startEventReached, &$finalEventReached) {
            if ($event->event_class === MatchingIncomingIntent::class && $event->event_properties['loopNo'] == $this->loopNo) {
                $startEventReached = true;
                return true;
            }

            if ($event->event_class === MatchingIncomingIntent::class && $event->event_properties['loopNo'] > $this->loopNo) {
                $finalEventReached = true;
                return false;
            }

            if ($event->event_class === TopRankedIntent::class) {
                $finalEventReached = true;
                return true;
            }

            return $startEventReached && !$finalEventReached;
        });
    }

    /**
     * @param Collection $selectedConversationObjects
     * @param Collection $filterConversationObjects
     */
    protected function annotateSelectedFilteredNodes(
        Collection $selectedConversationObjects,
        Collection $filterConversationObjects
    ): void {
        $selectedConversationObjects->each(function ($nodeId) {
            $this->setNodeStatus($nodeId, BaseData::CONSIDERED);
        });

        $filterConversationObjects->each(function ($nodeId) {
            $this->setNodeStatus($nodeId, BaseData::SELECTED);
            $this->annotateNode($nodeId, ['passingConditions' => true]);
        });

        $selectedConversationObjects->diff($filterConversationObjects)->each(function ($nodeId) {
            $this->annotateNode($nodeId, ['passingConditions' => false]);
        });
    }
}
