<?php

namespace App\Console\Commands;

use App\Bot\Platform\Cli\Request;
use App\Bot\Platform\Cli\Runner;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class Cli extends Command
{
    protected $signature = 'bot:run';

    private Client $client;
    private Runner $runner;

    /**
     * Cli constructor.
     * @param Client $client
     * @param Runner $runner
     */
    public function __construct(Client $client, Runner $runner)
    {
        $this->client = $client;
        $this->runner = $runner;
        parent::__construct();
    }

    public function handle()
    {
        $userId = $this->ask('Hello, who are you?');

        $this->output->writeln("<info>Identified {$userId}</info>");

        $request = new Request($userId, 'WELCOME');

        $response = $this->sendRequest($request);

        $this->info($response->);


//        $chatRequest = $this->createWebchatRequest($user, 'trigger', $openingIntent);
//
//        while (!empty($chatRequest)) {
//            // Get the Utterance.
//            $utterance = $webChatSensor->interpret($chatRequest);
//
//            /** @var WebChatMessages $messageWrapper */
//            $messageWrapper = $odController->runConversation($utterance);
//
//            $messages = $messageWrapper->getMessageToPost();
//
//            $chatRequest = $this->renderMessages($user, $messages);
//        }
//
//        $this->output->writeln("<comment>The end</comment>");
    }

    protected function createWebchatRequest($user, $type, $intent = '', $text = '', $value = '')
    {
        $chatData = [
            'notification' => 'message',
            'user_id' => $user->email,
            'author' => $user->email,
            'content' => [
                'type' => $type,
                'author' => $user->email,
                'callback_id' => $intent,
                'data' => [
                    'text' => $text,
                    'value' => $value,
                    'date' => "Wed 16 Dec",
                    'time' => "11:50:40 AM",
                ],
                'mode' => 'webchat',
                'modeInstance' => 0,
                'user_id' => $user->email,
                'user' => [
                    "first_name" => $user->name,
                    "last_name" => "",
                    "email" => $user->email,
                    "external_id" => $user->id,
                ]
            ]
        ];

        return $this->createRequest('POST', json_encode($chatData));
    }

    /**
     * @param $user
     * @param $messages
     *
     * @return \Illuminate\Http\Request|\Symfony\Component\HttpFoundation\Request|void
     */
    protected function renderMessages($user, $messages)
    {
        if (empty($messages)) {
            $this->output->writeln("<info>Empty list of messages</info>");
            return;
        }

        foreach ($messages as $message) {
            $response = $this->renderMessage($user, $message);

            if (!empty($response)) {
                return $response;
            }
        }

        $freeTextResponse = $this->ask('');
        return $this->createWebchatRequest($user, 'text', '', $freeTextResponse);
    }

    protected function renderMessage($user, $message)
    {
        if (empty($message)) {
            $this->output->writeln("<info>Empty response</info>");
            return null;
        }

        $this->output->writeln("\n------------------------------------------------------------\n"
            . "<info>{$message['type']} message ({$message['intent']})</info>");

        if ($this->output->isVerbose()) {
            print_r($message);
        }

        switch ($message['type']) {
            case 'button':
                $optionsText = '';
                $options = [];

                foreach ($message['data']['buttons'] as $id => $button) {
                    $optionsText .= "   > {$button['text']} ({$button['callback_id']})\n";
                    $options[] = $button['text'];
                }

                $result = $this->askWithCompletion("<comment>{$message['data']['text']}</comment>"
                    . "\n<info> Button options:\n{$optionsText}</info>", $options, $message['data']['buttons'][0]['text']);
                $choice = array_search($result, $options);

                if ($choice === false) {
                    $this->output->writeln("<error>Foolish human! That was not an option.</error>");
                    return null;
                }

                return $this->createWebchatRequest(
                    $user,
                    'button_response',
                    $message['data']['buttons'][$choice]['callback_id'],
                    $message['data']['buttons'][$choice]['text']
                );

            case 'text':
                $this->output->writeln("<comment>{$message['data']['text']}</comment>");
                break;

            default:
                $this->output->writeln("<error>CLI cannot deal with a {$message['type']} response</error>");
                break;
        }
    }

    public function sendRequest(Request $request)
    {
        return $this->runner->recieve($request);
    }
}

