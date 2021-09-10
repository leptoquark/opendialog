<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseData;
use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Events\Intent\TopRankedIntent;
use OpenDialogAi\Core\Conversation\Events\Interpretation\SelectedIntent;
use OpenDialogAi\Core\Conversation\Events\Messages\SelectedMessage;
use OpenDialogAi\Core\Conversation\Events\Turn\SelectedSingleTurn;

class NewUserOutgoingFrame extends FrameDataResponse
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
        $selectedTurnId = $this->getTurnIdFromEvent(SelectedSingleTurn::class);
        $this->annotateNode($selectedTurnId, ['message' => 'user in turn']);
        $this->setNodeStatus($selectedTurnId, BaseData::SELECTED);

        $selectedIntent = $this->getIntentIdFromEvent(SelectedIntent::class);
        $this->setNodeStatus($selectedIntent, BaseData::SELECTED);
        $this->annotateNode($selectedIntent, ['message' => 'user in turn']);

        $selectedMessage = $this->getEvents(SelectedMessage::class)->first();
        $selectedMessageIntentId = $selectedMessage->event_properties['intentId'];
        $selectedMessageMessage = $selectedMessage->meta_data['message'];
        $this->annotateNode($selectedMessageIntentId, ['message' => $selectedMessageMessage]);
    }

    public function filterEvents(): void
    {
        $startEventReached = false;
        $finalEventReached = false;

        $this->events = $this->events->filter(function ($event) use (&$startEventReached, &$finalEventReached) {
            if ($event->event_class === TopRankedIntent::class) {
                $startEventReached = true;
                return true;
            }

            if ($event->event_class === SelectedMessage::class) {
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
