<?php

namespace App\Http\Responses\FrameData;

use Illuminate\Support\Collection;
use OpenDialogAi\Core\Conversation\ConversationCollection;
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
    public const NOT_SELECTED   = 'not_selected';

    public string $label;

    public ?string $speaker = "";

    public array $data = [];

    public string $id;

    public string $status = self::NOT_CONSIDERED;

    public string $type;

    public ?string $parentId;

    public function __construct(string $label, string $id, ?string $parentId = null)
    {
        $this->label = $label;
        $this->id = $id;
        $this->parentId = $parentId;
    }

    public static function fromConversationObject(ConversationObject $object, string $parentId = null): BaseData
    {
        return new static($object->getName(), $object->getUid(), $parentId);
    }

    public static function generateConversationNodesFromScenario(Scenario $scenario): Collection
    {
        $nodes = new Collection();

        $nodes->add(ScenarioData::fromConversationObject($scenario));

        return$nodes->concat(self::generateConversationNodesFromConversations($scenario->getConversations()));
    }

    public static function generateConversationNodesFromConversations(ConversationCollection $conversations): Collection
    {
        $nodes = new Collection();

        $scenes = new Collection();
        $conversations->each(function (Conversation $conversation) use (&$scenes, $nodes) {
            $nodes->add(
                ConversationData::fromConversationObject($conversation, $conversation->getScenario()->getUid())
            );
            $scenes = $scenes->concat($conversation->getScenes() ?? $scenes);
        });

        return $nodes->concat(self::generateConversationNodesFromScenes($scenes));
    }

    /**
     * @param $scenes
     * @return Collection
     */
    public static function generateConversationNodesFromScenes($scenes)
    {
        $nodes = new Collection();
        $turns = new Collection();
        $scenes->each(function (Scene $scene) use (&$turns, $nodes) {
            $nodes->add(
                SceneData::fromConversationObject($scene, $scene->getConversation()->getUid())
            );
            $turns = $turns->concat($scene->getTurns());
        });

        return $nodes->concat(self::generateConversationNodesFromTurns($turns));
    }

    /**
     * @param $turns
     * @return Collection
     */
    public static function generateConversationNodesFromTurns($turns)
    {
        $nodes = new Collection;
        $turns->each(function (Turn $turn) use ($nodes) {
            $nodes->add(TurnData::fromConversationObject($turn, $turn->getScene()->getUid()));
            $nodes->add(IntentCollectionData::fromTurn($turn, $turn->getRequestIntents(), 'request'));
            $nodes->add(IntentCollectionData::fromTurn($turn, $turn->getResponseIntents(), 'response'));

            $turn->getRequestIntents()->each(function (Intent $intent) use ($nodes) {
                $nodes->add(
                    IntentData::fromIntent($intent, $intent->getTurn(), 'request')
                );
            });

            $turn->getResponseIntents()->each(function (Intent $intent) use ($nodes) {
                $nodes->add(
                    IntentData::fromIntent($intent, $intent->getTurn(), 'response')
                );
            });
        });

        return $nodes;
    }

    public function toArray()
    {
        $data = [
            "type" => $this->type,
            "label" => $this->label,
            "id" => $this->id,
            "status" => $this->status,
            "data" => $this->data
        ];

        if ($this->speaker) {
            $data['speaker'] = $this->speaker;
        }

        return $data;
    }

    /**
     * Generates a node connection array for use in the response
     *
     * @return array The connect data for response
     */
    public function generateConnection(): array
    {
        return [
            'data' => [
                'id' => $this->parentId . '-' . $this->id,
                'source' => $this->parentId,
                'target' => $this->id,
                'status' => $this->status,
                'parent' => $this->parentId
            ]
        ];
    }
}
