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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class FocusedConversationResource extends JsonResource
{
    public static $wrap = null;

    public static array $fields = [
        AbstractNormalizer::ATTRIBUTES => [
            Conversation::UID,
            Conversation::OD_ID,
            Conversation::NAME,
            Conversation::DESCRIPTION,
            Conversation::INTERPRETER,
            Conversation::CREATED_AT,
            Conversation::UPDATED_AT,
            Conversation::SCENARIO => [
                Scenario::UID,
                Scenario::OD_ID,
                Scenario::NAME,
                Scenario::DESCRIPTION
            ],
            Conversation::BEHAVIORS =>[
                Behavior::COMPLETING_BEHAVIOR
            ],
            Conversation::CONDITIONS => [
                Condition::OPERATION,
                Condition::OPERATION_ATTRIBUTES,
                Condition::PARAMETERS
            ],
            Conversation::SCENES => [
                Scene::UID,
                Scene::OD_ID,
                Scene::NAME,
                Scene::DESCRIPTION,
                Scene::BEHAVIORS => Behavior::FIELDS,
                Scene::CONDITIONS => Condition::FIELDS,
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
        $normalizedConversation = Serializer::normalize($this->resource, 'json', self::$fields);

        $data = $this->rearrangeData($normalizedConversation);

        return $this->addMetaData($data);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function rearrangeData(array $data): array
    {
        $normalizedFocussedConversation = [];

        $normalizedFocussedConversation['scenario'] = $data['scenario'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['id'] = $data['id'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['od_id'] = $data['od_id'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['name'] = $data['name'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['description'] = $data['description'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['interpreter'] = $data['interpreter'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['created_at'] = $data['created_at'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['updated_at'] = $data['updated_at'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['behaviors'] = $data['behaviors'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['conditions'] = $data['conditions'];
        $normalizedFocussedConversation['scenario']['focusedConversation']['scenes'] = $data['scenes'];

        return $normalizedFocussedConversation;
    }

    protected function addMetaData(array $data)
    {
        $scenes = collect($data['scenario']['focusedConversation']['scenes']);
        $sceneUids = $scenes->pluck('id');

        $intentsWithTransitionsToScenes = TransitionDataClient::getIncomingSceneTransitions(...$sceneUids);

        $scenes = $scenes->map(function ($scene) use ($intentsWithTransitionsToScenes) {
            $intent = $intentsWithTransitionsToScenes
                ->filter(fn (Intent $i) => $i->getTransition() && $i->getTransition()->getScene() == $scene['id'])
                ->values();

            $scene['_meta']['incoming_transitions'] = Serializer::normalize($intent, 'json', [
                AbstractNormalizer::ATTRIBUTES => [
                    Intent::UID,
                    Intent::OD_ID,
                    Intent::TRANSITION => Transition::FIELDS,
                ],
            ]);

            return $scene;
        });

        $data['scenario']['focusedConversation']['scenes'] = $scenes;

        return $data;
    }
}
