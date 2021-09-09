<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Conversation;
use OpenDialogAi\Core\Conversation\Scenario;

class ScenarioData extends BaseData
{
    public string $type = 'scenario';

    public static function fromScenario(Scenario $scenario)
    {
        $scenarioData = self::fromConversationObject($scenario);

        $scenario->getConversations()->each(function (Conversation $conversation) use ($scenarioData) {
            $scenarioData->children->add(ConversationData::fromConversation($conversation));
        });

        return $scenarioData;
    }
}
