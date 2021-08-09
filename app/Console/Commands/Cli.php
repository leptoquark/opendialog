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

        // new / returning
        $request = new Request($userId, 'WELCOME');

        $response = $this->sendRequest($request);

        while (true) {
            foreach ($response as $message) {
                $userInput = $this->ask($message['data']['text']);

                if ($userInput === 'exit') {
                    $this->info('bye bye');
                    break 2;
                }

                if ($userInput === 'restart') {
                    $request = new Request($userId, 'intent.core.restart');
                } else if ($userInput === 'end chat') {
                    $request = new Request($userId, 'intent.core.end_chat');
                } else {
                    $request = new Request($userId, '', $userInput);
                }

                $response = $this->sendRequest($request);
            }
        }
    }

    public function sendRequest(Request $request)
    {
        return $this->runner->recieve($request);
    }
}

