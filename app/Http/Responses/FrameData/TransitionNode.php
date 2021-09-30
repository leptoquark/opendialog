<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;

class TransitionNode extends BaseNode
{
    public string $type = 'intent';

    public static function fromTransitionEvent(StoredEvent $transitionEvent)
    {
        $transitionNode = new self(
            $transitionEvent->getObjectName(),
            'transition',
        );

        $transitionNode->status = 'transition';
        $transitionNode->shouldDraw = true;

        return $transitionNode;
    }
}