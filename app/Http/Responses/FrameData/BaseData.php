<?php

namespace App\Http\Responses\FrameData;

use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\ConversationObject;
use OpenDialogAi\Core\Conversation\Intent;
use OpenDialogAi\Core\Conversation\Scenario;
use OpenDialogAi\Core\Conversation\Conversation;
use OpenDialogAi\Core\Conversation\Scene;
use OpenDialogAi\Core\Conversation\Turn;

abstract class BaseData
{
    // Statuses
    public const NOT_CONSIDERED = 'not_considered';
    public const CONSIDERED     = 'considered';
    public const SELECTED       = 'selected';

    public string $label;

    public string $id;

    public string $status = self::NOT_CONSIDERED;

    public string $type;

    public array $data = [];

    public ?string $parentId;

    public function __construct(string $label, string $id, ?string $parentId = null)
    {
        $this->label = $label;
        $this->id = $id;
        $this->parentId = $parentId;
    }

    public static function fromConversationObject(ConversationObject $object, string $parentId = null)
    {
        return new static($object->getName(), $object->getUid(), $parentId);
    }

    public static function generateConversationNodesFromScenario(Scenario $scenario): Collection
    {
        $nodes = new Collection();

        $nodes->add(ScenarioData::fromConversationObject($scenario));

        $conversations = $scenario->getConversations();

        $scenes = new Collection();
        $conversations->each(function (Conversation $conversation) use (&$scenes, $nodes) {
            $nodes->add(
                ConversationData::fromConversationObject($conversation, $conversation->getScenario()->getUid())
            );
            $scenes = $scenes->concat($conversation->getScenes() ?? $scenes);
        });

        $turns = new Collection();
        $scenes->each(function (Scene $scene) use (&$turns, $nodes) {
            $nodes->add(
                SceneData::fromConversationObject($scene, $scene->getConversation()->getUid())
            );
            $turns = $turns->concat($scene->getTurns());
        });

        $intents = new Collection();
        $turns->each(function (Turn $turn) use (&$intents, $nodes) {
            $nodes->add(
                TurnData::fromConversationObject($turn, $turn->getScene()->getUid())
            );
            $intents = $intents->concat($turn->getRequestIntents());
            $intents = $intents->concat($turn->getResponseIntents());
        });

        $intents->each(function (Intent $intent) use ($nodes) {
            $nodes->add(
                IntentData::fromConversationObject($intent, $intent->getTurn()->getUid())
            );
        });

        return $nodes;
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

    /**
     * Generates a node connection array for use in the response
     *
     * @return array The connect data for response
     */
    public function generateConnection(): array
    {
        return [
            'id' => $this->parentId . '-' . $this->id,
            'source' => $this->parentId,
            'target' => $this->id,
            'status' => $this->status
        ];
    }
}
