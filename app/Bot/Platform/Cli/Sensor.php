<?php


namespace App\Bot\Platform\Cli;

use Illuminate\Http\Request;
use OpenDialogAi\AttributeEngine\CoreAttributes\UserAttribute;
use OpenDialogAi\AttributeEngine\CoreAttributes\UtteranceAttribute;
use OpenDialogAi\SensorEngine\BaseSensor;

class Sensor extends BaseSensor
{

    // remove the type
    public function interpret(Request $request): UtteranceAttribute
    {
        // won't work
    }

    public function interpreter(\App\Bot\Platform\Cli\Request $request): UtteranceAttribute
    {
        $utterance = new UtteranceAttribute('utterance');
        $utterance
            ->setPlatform('cli')
            ->setUtteranceAttribute(UtteranceAttribute::UTTERANCE_DATA, $request->text)
            ->setUtteranceAttribute(UtteranceAttribute::CALLBACK_ID, $request->callbackId)
            ->setUtteranceAttribute(UtteranceAttribute::UTTERANCE_USER_ID, $request->userId);

        $utterance->setUtteranceAttribute(
            'utterance_user',
            $this->createUser($request->userId)
        );

        return $utterance;
    }

    protected function createUser(string $userId): UserAttribute
    {
        $user = new UserAttribute(UtteranceAttribute::UTTERANCE_USER);
        $user->setUserId($userId);
        return $user;
    }
}