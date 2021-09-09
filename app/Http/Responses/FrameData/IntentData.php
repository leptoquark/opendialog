<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Intent;

class IntentData extends BaseData
{
    public string $type = 'intent';

    public static function fromIntent(Intent $intent)
    {
        return self::fromConversationObject($intent);
    }
}
