<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Turn;

class AppIntentsData extends BaseData
{
    public string $type = 'app_intents';

    public static function fromTurn(Turn $turn)
    {
        return new static('App Intents', $turn->getUid() . "_app", $turn->getUid());
    }
}