<?php

namespace App\Http\Responses;

use App\Http\Responses\FrameData\ScenarioData;
use OpenDialogAi\Core\Conversation\Events\Scenario\FilteredScenarios;
use OpenDialogAi\Core\Conversation\Events\Scenario\SelectedAllScenarios;

class NewUserInitialFrame extends FrameDataResponse
{
    public array $relevantEvents = [
        SelectedAllScenarios::class,
        FilteredScenarios::class
    ];

    public function generateResponse()
    {
        // Update status for all considered scenarios
        $selectedScenarios = $this->getScenarioIdsFromEvent(SelectedAllScenarios::class);
        $filteredScenarios = $this->getScenarioIdsFromEvent(FilteredScenarios::class);

        $selectedScenarios->each(function ($scenarioId) {
            $this->setScenarioStatus($scenarioId, ScenarioData::CONSIDERED);
        });

        $filteredScenarios->each(function ($scenarioId) {
            $this->setScenarioStatus($scenarioId, ScenarioData::SELECTED);
            $this->annotateScenario($scenarioId, ['passingConditions' => true]);
        });

        $selectedScenarios->diff($filteredScenarios)->each(function ($scenarioId) {
            $this->annotateScenario($scenarioId, ['passingConditions' => false]);
        });

        return $this->formatResponse();
    }
}
