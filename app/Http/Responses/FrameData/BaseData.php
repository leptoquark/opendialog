<?php

namespace App\Http\Responses\FrameData;

use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\ConversationObject;

abstract class BaseData
{
    // Statuses
    public const NOT_CONSIDERED = 'not_considered';
    public const CONSIDERED = 'considered';
    public const SELECTED = 'selected';

    public string $label;

    public string $id;

    public string $status = self::NOT_CONSIDERED;

    public string $type;

    public array $data = [];

    public Collection $children;

    public function __construct(string $label, string $id)
    {
        $this->label = $label;
        $this->id = $id;
        $this->children = new Collection();
    }

    public static function fromConversationObject(ConversationObject $object)
    {
        return new static($object->getName(), $object->getUid());
    }

    public function toArray()
    {
        return [
            "type" => $this->type,
            "label" => $this->label,
            "id" => $this->id,
            "status" => $this->status,
            "data" => $this->data
        ];
    }
}
