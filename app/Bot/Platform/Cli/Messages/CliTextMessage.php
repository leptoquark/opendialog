<?php

namespace App\Bot\Platform\Cli\Messages;

use OpenDialogAi\ResponseEngine\Message\TextMessage;

class CliTextMessage extends CliMessage implements TextMessage
{
    protected $messageType = self::TYPE;

    public function isEmpty(): bool
    {
        return false;
    }
}
