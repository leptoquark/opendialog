<?php

namespace App\Http\Responses\FrameData;

use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\Intent;
use OpenDialogAi\Core\Conversation\Turn;

class TurnData extends BaseData
{
    public string $type = 'turn';

    public static function fromTurn(Turn $turn)
    {
        $turnData = self::fromConversationObject($turn);
        $turnData->children = new Collection();

        if ($turn->getRequestIntents()) {
            $turn->getRequestIntents()->each(function (Intent $intent) use ($turnData) {
                $turnData->children->add(IntentData::fromIntent($intent));
            });
        }

        if ($turn->getResponseIntents()) {
            $turn->getResponseIntents()->each(function (Intent $intent) use ($turnData) {
                $turnData->children->add(IntentData::fromIntent($intent));
            });
        }

        return $turnData;
    }
}