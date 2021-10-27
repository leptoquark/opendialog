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
use OpenDialogAi\Core\Conversation\Scene;
use OpenDialogAi\Core\Conversation\Transition;
use OpenDialogAi\Core\Conversation\Turn;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class FocusedSceneResource extends JsonResource
{
    public static $wrap = null;

    public static array $fields = [
        AbstractNormalizer::ATTRIBUTES => [
            Scene::UID,
            Scene::OD_ID,
            Scene::NAME,
            Scene::DESCRIPTION,
            Scene::INTERPRETER,
            Scene::CREATED_AT,
            Scene::UPDATED_AT,
            Scene::CONVERSATION => [
                Conversation::UID,
                Conversation::OD_ID,
                Conversation::NAME,
                Conversation::DESCRIPTION,
                Conversation::SCENARIO => [
                    Scenario::UID,
                    Scenario::OD_ID,
                    Scenario::NAME,
                    Scenario::DESCRIPTION
                ]
            ],
            Scene::BEHAVIORS => Behavior::FIELDS,
            Scene::CONDITIONS => [
                Condition::OPERATION,
                Condition::OPERATION_ATTRIBUTES,
                Condition::PARAMETERS
            ],
            Scene::TURNS => [
                Turn::UID,
                Turn::OD_ID,
                Turn::NAME,
                Turn::DESCRIPTION,
                Turn::BEHAVIORS => Behavior::FIELDS,
                Turn::CONDITIONS => Condition::FIELDS,
                Turn::REQUEST_INTENTS => [
                    Intent::UID,
                    Intent::OD_ID,
                    Intent::SPEAKER,
                    Intent::BEHAVIORS => Behavior::FIELDS,
                    Intent::CONDITIONS => Condition::FIELDS,
                    Intent::TRANSITION => Transition::FIELDS,
                ],
                Turn::RESPONSE_INTENTS => [
                    Intent::UID,
                    Intent::OD_ID,
                    Intent::SPEAKER,
                    Intent::BEHAVIORS => Behavior::FIELDS,
                    Intent::CONDITIONS => Condition::FIELDS,
                    Intent::TRANSITION => Transition::FIELDS,
                ],
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
        // reshape the response renaming conversation to focussedConversation
        $normalizedScene = Serializer::normalize($this->resource, 'json', self::$fields);

        $data = $this->rearrangeData($normalizedScene);

        return $this->addMetaData($data);
    }

    /**
     * @param $normalizedScene
     * @return array
     */
    protected function rearrangeData($normalizedScene): array
    {
        $normalizedFocusedScene = [];
        $normalizedFocusedScene['scenario'] =
            $normalizedScene['conversation']['scenario'];
        $normalizedFocusedScene['scenario']['conversation']['id'] =
            $normalizedScene['conversation']['id'];
        $normalizedFocusedScene['scenario']['conversation']['od_id'] =
            $normalizedScene['conversation']['od_id'];
        $normalizedFocusedScene['scenario']['conversation']['name'] =
            $normalizedScene['conversation']['name'];
        $normalizedFocusedScene['scenario']['conversation']['description'] =
            $normalizedScene['conversation']['description'];

        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['id'] =
            $normalizedScene['id'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['od_id'] =
            $normalizedScene['od_id'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['name'] =
            $normalizedScene['name'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['description'] =
            $normalizedScene['description'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['updated_at'] =
            $normalizedScene['updated_at'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['created_at'] =
            $normalizedScene['created_at'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['interpreter'] =
            $normalizedScene['interpreter'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['behaviors'] =
            $normalizedScene['behaviors'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['conditions'] =
            $normalizedScene['conditions'];
        $normalizedFocusedScene['scenario']['conversation']['focusedScene']['turns'] =
            $normalizedScene['turns'];

        return $normalizedFocusedScene;
    }

    protected function addMetaData(array $data)
    {
        $turns = collect($data['scenario']['conversation']['focusedScene']['turns']);
        $turnUids = $turns->pluck('id');

        $intentsWithTransitionsToTurns = TransitionDataClient::getIncomingTurnTransitions(...$turnUids);

        $turns = $turns->map(function ($turn) use ($intentsWithTransitionsToTurns) {
            $intentsWithTransitionsToTurn = $intentsWithTransitionsToTurns
                ->filter(fn (Intent $i) => $i->getTransition() && $i->getTransition()->getTurn() == $turn['id'])
                ->values();

            $intents = collect($turn['intents'])
                ->map(fn ($i) => $i['intent']);

            $intentsWithOutgoingTransition = $intents
                ->filter(fn ($i) => !is_null($i['transition']) && !is_null($i['transition']['conversation']))
                ->values();

            $intentsWithCompletingBehaviour = $intents
                ->filter(fn ($i) => in_array(Behavior::COMPLETING_BEHAVIOR, $i['behaviors']))
                ->values();

            $turn['_meta']['incoming_transitions'] = Serializer::normalize($intentsWithTransitionsToTurn, 'json', [
                AbstractNormalizer::ATTRIBUTES => [
                    Intent::UID,
                    Intent::OD_ID,
                    Intent::TRANSITION => Transition::FIELDS,
                ],
            ]);
            $turn['_meta']['outgoing_transitions'] = $intentsWithOutgoingTransition;
            $turn['_meta']['completing_intents'] = $intentsWithCompletingBehaviour;

            return $turn;
        });

        $data['scenario']['conversation']['focusedScene']['turns'] = $turns;

        return $data;
    }
}
