<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Turn;

class UserIntentsData extends BaseData
{
    public string $type = 'user_intents';

    public static function fromTurn(Turn $turn)
    {
        return new static('User Intents', $turn->getUid() . "_user", $turn->getUid());
    }
}