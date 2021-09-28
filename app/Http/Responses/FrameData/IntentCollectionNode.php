<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\IntentCollection;
use OpenDialogAi\Core\Conversation\Turn;

class IntentCollectionNode extends BaseNode
{
    public static function fromTurn(Turn $turn, IntentCollection $intents, $type)
    {
        $requestIntents = new static(
            sprintf('%s Intents', ucfirst($type)),
            sprintf('%s_%s', $turn->getUid(), $type),
            $turn->getUid()
        );
        $requestIntents->speaker = $intents->first() ? $intents->first()->getSpeaker() : null;
        $requestIntents->type = sprintf('%s_intents', $type);

        return $requestIntents;
    }
}