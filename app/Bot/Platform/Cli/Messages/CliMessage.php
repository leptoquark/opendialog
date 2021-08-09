<?php

namespace App\Bot\Platform\Cli\Messages;

use OpenDialogAi\ResponseEngine\Message\OpenDialogMessage;

abstract class CliMessage implements OpenDialogMessage
{
    protected $messageType = 'base';

    const TIME = 'time';

    const DATE = 'date';

    /** The message text. */
    private $text = null;

    private $time;

    private $date;

    private $intent;

    public function __construct()
    {
        $this->time = date('h:i A');
        $this->date = date('D j M');
    }

    /**
     * Sets text for a standard Web Chat message.
     *
     * @param $text - main message text
     * @return $this
     */
    public function setText($text)
    {
        if (is_null($text) || $text == "") {
            $this->text = null;
        } else {
            $this->text = $text;
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getIntent()
    {
        return $this->intent;
    }

    /**
     * Set intent property
     *
     * @param $intent
     * @return $this
     */
    public function setIntent(string $intent): OpenDialogMessage
    {
        $this->intent = $intent;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): ?array
    {
        return [
            'text' => $this->getText(),
            self::TIME => $this->getTime(),
            self::DATE => $this->getDate()
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getMessageToPost()
    {
        return [
            'author' => 'them',
            'type' => $this->getMessageType(),
            'intent' => $this->getIntent(),
            'data' => $this->getData()
        ];
    }
}
