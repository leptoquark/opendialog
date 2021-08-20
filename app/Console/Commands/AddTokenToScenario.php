<?php

namespace App\Console\Commands;

use App\ScenarioAccessToken;
use App\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;
use OpenDialogAi\Core\Conversation\Facades\ConversationDataClient;

class AddTokenToScenario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scenario:add-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a sanctum token for each scenario';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (ConversationDataClient::getAllScenarios() as $scenario) {
            $this->info("Getting scenario: " . $scenario->getUid());
            $tokenName = 'scenario:' . $scenario->getUid();
            $accessToken = PersonalAccessToken::where("name", $tokenName)->first();
            $botUser = User::where("name", config('opendialog.core.bot_user'))->first();
            if (is_null($botUser)) {
                $this->error('In order to generate access tokens you will need to configure a bot user');
                return 1;
            }

            if (!$accessToken) {
                $this->info("This scenario does NOT have an access token, creating one..." );
                $token = $botUser->createToken($tokenName, [$tokenName])->plainTextToken;
                ScenarioAccessToken::create([
                    'scenario_id' => $scenario->getUid(),
                    'access_token_plaintext' => $token
                ]);
            } else {
                $this->info("This scenario does have an access token, ignoring..." );
            }
        }
        return 0;
    }
}
