<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\ScenarioData;
use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Events\Scenario\FilteredScenarios;
use OpenDialogAi\Core\Conversation\Events\Scenario\SelectedAllScenarios;

class NewUserInitialFrame extends FrameDataResponse
{
    public array $relevantEvents = [
        SelectedAllScenarios::class,
        FilteredScenarios::class
    ];

    protected function annotateNodes(): void
    {
        // Update status for all considered scenarios
        $selectedScenarios = $this->getScenarioIdsFromEvent(SelectedAllScenarios::class);
        $filteredScenarios = $this->getScenarioIdsFromEvent(FilteredScenarios::class);

        $selectedScenarios->each(function ($scenarioId) {
            $this->setNodeStatus($scenarioId, ScenarioData::CONSIDERED);
        });

        $filteredScenarios->each(function ($scenarioId) {
            $this->setNodeStatus($scenarioId, ScenarioData::SELECTED);
            $this->annotateNode($scenarioId, ['passingConditions' => true]);
        });

        $selectedScenarios->diff($filteredScenarios)->each(function ($scenarioId) {
            $this->annotateNode($scenarioId, ['passingConditions' => false]);
        });
    }

    protected function filterEvents(): void
    {
        $this->events =  $this->events->whereIn('event_class', $this->relevantEvents);
    }
}
