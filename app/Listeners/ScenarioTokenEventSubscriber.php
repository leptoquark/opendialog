<?php


namespace App\Listeners;


use App\ScenarioAccessToken;
use App\User;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use OpenDialogAi\Core\Conversation\Events\ConversationObjectCreated;
use OpenDialogAi\Core\Conversation\Events\ConversationObjectDeleted;
use OpenDialogAi\Core\Conversation\Events\ConversationObjectUpdated;
use OpenDialogAi\Core\Conversation\Scenario;

class ScenarioTokenEventSubscriber
{
    /**
     * Handle conversation object creation events.
     * @param $event
     */
    public function handleScenarioCreated($event)
    {
        if ($this->isScenario($event)) {
            $tokenName = $this->getTokenName($event);
            $botUser = $this->getBotUser();

            if (is_null($botUser)) {
                Log::error('In order to generate access tokens you will need to configure a bot user');
                return;
            }

            $accessToken = PersonalAccessToken::where("name", $tokenName)->first();
            if (!$accessToken) {
                $token = $botUser->createToken($tokenName, [$tokenName])->plainTextToken;
                ScenarioAccessToken::create([
                    'scenario_id' => $event->conversationObject->getUid(),
                    'access_token_plaintext' => $token
                ]);
            }
        }
    }

    /**
     * Handle conversation object update events.
     * @param $event
     */
    public function handleScenarioUpdated($event)
    {
        if ($this->isScenario($event)) {
            $tokenName = $this->getTokenName($event);
            $accessToken = PersonalAccessToken::where("name", $tokenName)->first();
            if ($this->has($accessToken)) {
                if ($event->conversationObject->isActive() && $accessToken->cant('active')) {
                    $accessToken->abilities = $this->addValueToAbilities($accessToken, 'active');
                    $accessToken->save();
                } elseif (!$event->conversationObject->isActive() && $accessToken->can('active')) {
                    $accessToken->abilities = $this->removeValueFromAbilities($accessToken, 'active');
                    $accessToken->save();
                }
            }
        }
    }


    /**
     * Handle conversation object deletion events.
     * @param $event
     */
    public function handleScenarioDeleted($event)
    {
        if ($this->isScenario($event)) {
            PersonalAccessToken::where("name", 'scenario:' . $event->conversationObjectUid)->delete();
            ScenarioAccessToken::where("scenario_id", $event->conversationObjectUid)->delete();
        }
    }
    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(
            ConversationObjectCreated::class,
            [ScenarioTokenEventSubscriber::class, 'handleScenarioCreated']
        );

        $events->listen(
            ConversationObjectUpdated::class,
            [ScenarioTokenEventSubscriber::class, 'handleScenarioUpdated']
        );

        $events->listen(
            ConversationObjectDeleted::class,
            [ScenarioTokenEventSubscriber::class, 'handleScenarioDeleted']
        );
    }

    /**
     * @param $event
     * @return bool
     */
    private function isScenario($event): bool
    {
        return $event->conversationObjectType === Scenario::class;
    }

    /**
     * @param $event
     */
    private function getTokenName($event): string
    {
        $uid = $event->conversationObject->getUid();
        return 'scenario:' . $uid;
    }

    /**
     * @return mixed
     */
    private function getBotUser()
    {
        return User::where("email", config('sanctum.bot_user'))->first();
    }

    /**
     * @param $accessToken
     * @param $ability
     * @return mixed
     */
    private function removeValueFromAbilities($accessToken, $ability)
    {
        $updateAbilities = $accessToken->abilities;
        if (($key = array_search($ability, $updateAbilities)) !== false) {
            unset($updateAbilities[$key]);
        }
        return $updateAbilities;
    }

    /**
     * @param $accessToken
     * @param $ability
     * @return array
     */
    private function addValueToAbilities($accessToken, $ability): array
    {
        return array_merge($accessToken->abilities, [$ability]);
    }

    /**
     * @param $accessToken
     * @return bool
     */
    private function has($accessToken): bool
    {
        return !is_null($accessToken);
    }

}
