<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Turn;

class ResponseIntentsData extends BaseData
{
    public string $type = 'response_intents';

    public static function fromTurn(Turn $turn)
    {
        return new static('Response Intents', $turn->getUid() . "_response", $turn->getUid());
    }
}