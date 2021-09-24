<?php


namespace App\Http\Resources;

use App\Http\Facades\Serializer;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenDialogAi\Core\Conversation\Behavior;
use OpenDialogAi\Core\Conversation\Condition;
use OpenDialogAi\Core\Conversation\Conversation;
use OpenDialogAi\Core\Conversation\Facades\TransitionDataClient;
use OpenDialogAi\Core\Conversation\Intent;
use OpenDialogAi\Core\Conversation\Scenario;
use OpenDialogAi\Core\Conversation\Transition;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class FocusedScenarioResource extends JsonResource
{
    public static $wrap = null;

    public static array $fields = [
        AbstractNormalizer::ATTRIBUTES => [
            Scenario::UID,
            Scenario::OD_ID,
            Scenario::NAME,
            Scenario::DESCRIPTION,
            Scenario::INTERPRETER,
            Scenario::CREATED_AT,
            Scenario::UPDATED_AT,
            Scenario::ACTIVE,
            Scenario::STATUS,
            Scenario::BEHAVIORS => Behavior::FIELDS,
            Scenario::CONDITIONS => Condition::FIELDS,
            Scenario::CONVERSATIONS => [
                Conversation::UID,
                Conversation::OD_ID,
                Conversation::NAME,
                Conversation::DESCRIPTION,
                Conversation::BEHAVIORS => Behavior::FIELDS,
                Conversation::CONDITIONS => Condition::FIELDS,
            ]
        ]
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $normalizedScenario = Serializer::normalize($this->resource, 'json', self::$fields);

        $data = $this->rearrangeData($normalizedScenario);

        return $this->addMetaData($data);
    }

    /**
     * @param $normalizedScenario
     * @return array
     */
    protected function rearrangeData($normalizedScenario): array
    {
        return ['focusedScenario' => $normalizedScenario];
    }

    protected function addMetaData(array $data)
    {
        $conversationUids = array_map(fn ($s) => $s['id'], $data['focusedScenario']['conversations']);
        $intentsWithTransitionsToScenes = TransitionDataClient::getIncomingConversationTransitions(...$conversationUids);

        $data['focusedScenario']['_meta'] = [
            'incoming_transitions' => Serializer::normalize($intentsWithTransitionsToScenes, 'json', [
                AbstractNormalizer::ATTRIBUTES => [
                    Intent::UID,
                    Intent::OD_ID,
                    Intent::TRANSITION => Transition::FIELDS,
                ],
            ]),
        ];

        return $data;
    }
}
