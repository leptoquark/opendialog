<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Intent;
use OpenDialogAi\Core\Conversation\Turn;

class IntentNode extends BaseNode
{
    public string $type = 'intent';

    public static function fromIntent(Intent $intent, Turn $turn, string $type)
    {
        return new static(
            $intent->getName(),
            $intent->getUid(),
            $turn->getUid() . "_" . $type
        );
    }
}
