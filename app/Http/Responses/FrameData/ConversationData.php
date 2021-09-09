<?php

namespace App\Http\Responses\FrameData;

use OpenDialogAi\Core\Conversation\Conversation;
use OpenDialogAi\Core\Conversation\Scene;

class ConversationData extends BaseData
{
    public string $type = 'conversation';

    public static function fromConversation(Conversation $conversation)
    {
        $conversationData = self::fromConversationObject($conversation);

        if ($conversation->getScenes()) {
            $conversation->getScenes()->each(function (Scene $scene) use ($conversationData) {
                $conversationData->children->add(SceneData::fromScene($scene));
            });
        }

        return $conversationData;
    }
}
