<?php


namespace App\Listeners;


use App\ScenarioAccessToken;
use App\User;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use OpenDialogAi\Core\Conversation\Events\ConversationObjectCreated;
use OpenDialogAi\Core\Conversation\Events\ConversationObjectUpdated;
use OpenDialogAi\Core\Conversation\Scenario;

class CreateScenarioAccessToken
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \OpenDialogAi\Core\Conversation\Events\ConversationObjectCreated  $event
     * @return void
     */
    public function handle(ConversationObjectCreated $event)
    {
        if ($event->conversationObjectType === Scenario::class) {
            $uid = $event->conversationObject->getUid();
            $tokenName = 'scenario:' . $uid;

            $botUser = User::where("name", config('opendialog.core.bot_user'))->first();

            if (is_null($botUser)) {
                Log::error('In order to generate access tokens you will need to configure a bot user');
                return;
            }

            $accessToken = PersonalAccessToken::where("name", $tokenName)->first();
            if (!$accessToken) {
                $token = $botUser->createToken($tokenName, [$tokenName, 'billable:true'])->plainTextToken;
                ScenarioAccessToken::create([
                    'scenario_id' => $uid,
                    'access_token_plaintext' => $token
                ]);
            }
        }
    }
}
