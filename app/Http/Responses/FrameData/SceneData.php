<?php

namespace App\Http\Responses\FrameData;

use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Conversation;
use OpenDialogAi\Core\Conversation\Scene;
use OpenDialogAi\Core\Conversation\Turn;

class SceneData extends BaseData
{
    public string $type = 'scene';

    public static function fromScene(Scene $scene)
    {
        $sceneData = self::fromConversationObject($scene);
        $sceneData->children = new Collection();

        if ($scene->getTurns()) {
            $scene->getTurns()->each(function (Turn $turn) use ($sceneData) {
                $sceneData->children->add(TurnData::fromTurn($turn));
            });
        }

        return $sceneData;
    }
}