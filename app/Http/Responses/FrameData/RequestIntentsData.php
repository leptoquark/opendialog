<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Turn;

class RequestIntentsData extends BaseData
{
    public string $type = 'request_intents';

    public static function fromTurn(Turn $turn)
    {
        return new static('Request Intents', $turn->getUid() . "_request", $turn->getUid());
    }
}