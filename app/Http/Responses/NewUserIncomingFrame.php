<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\BaseData;
use App\Http\Responses\FrameData\ScenarioData;
use OpenDialogAi\Core\Conversation\Events\Conversation\FilteredConversations;
use OpenDialogAi\Core\Conversation\Events\Conversation\SelectedStartingConversations;
use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Scenario\FilteredScenarios;
use OpenDialogAi\Core\Conversation\Events\Scene\FilteredScenes;

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

    public function generateResponse()
    {
        $this->filterEvents();

        // Show selected scenarios
        $this->getScenarioIdsFromEvent(FilteredScenarios::class)->each(function ($scenarioId) {
            $this->setScenarioStatus($scenarioId, BaseData::SELECTED);
            $this->annotateScenario($scenarioId, ['passingConditions' => true]);
        });

        $selectedConversations = $this->getConversationIdsFromEvent(SelectedStartingConversations::class);
        $filterConversations = $this->getConversationIdsFromEvent(FilteredConversations::class);

        $selectedConversations->each(function ($conversationId) {
            $this->setConversationStatus($conversationId, BaseData::CONSIDERED);
        });

        $filterConversations->each(function ($conversationId) {
            $this->setConversationStatus($conversationId, BaseData::SELECTED);
            $this->annotateConversation($conversationId, ['passingConditions' => true]);
        });

        $selectedConversations->diff($filterConversations)->each(function ($conversationId) {
            $this->annotateConversation($conversationId, ['passingConditions' => false]);
        });

        return $this->formatResponse();
    }

    public function filterEvents()
    {
        $startEventReached = false;
        $finalEventReached = false;

        $this->events = $this->events->filter(function ($event) use (&$startEventReached, &$finalEventReached) {
            if ($event->event_class === MatchingIncomingIntent::class) {
                if ($event->event_properties['loopNo'] == $this->loopNo) {
                    $startEventReached = true;
                }

                if ($event->event_properties['loopNo'] > $this->loopNo) {
                    $finalEventReached = true;
                }

                return true;
            }

            if ($event->event_class === FilteredScenes::class) {
                $finalEventReached = true;

                return true;
            }

            return $startEventReached && !$finalEventReached;
        });
    }
}
