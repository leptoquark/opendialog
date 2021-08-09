<?php

namespace App\Bot\Platform\Cli;

class Request
{
    public $type;
    public $userId;
    public $callbackId;
    public $text;
    public $attributeName;
    public $attributeValue;

    /**
     * Request constructor.
     * @param $userId
     * @param $callbackId
     * @param $text
     * @param $attributeName
     * @param $attributeValue
     */
    public function __construct($userId, $callbackId = null, $text = null, $attributeName = null, $attributeValue = null)
    {
        $this->userId = $userId;
        $this->callbackId = $callbackId;
        $this->text = $text;
//        $this->attributeName = $attributeName;
//        $this->attributeValue = $attributeValue;
    }

    public function format()
    {
        return [
            'user_id' => $this->userId,
            'callback_id' => $this->callbackId,
            'text' => $this->text,
        ];
    }
}
