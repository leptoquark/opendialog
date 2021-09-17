<?php

namespace App\Http\Controllers\API;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use RuntimeException;

class ExternalCssController
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getCss(Request $request)
    {
        try {
            $path = $request->get('path');

            if ($path) {
                $response = $this->client->get($path);
                if ($response->getHeader('Content-Type')[0] == 'text/css') {
                    return $response
                        ->getBody()
                        ->getContents();
                } else {
                    throw new RuntimeException("The URL provided does not contain css content");
                }
            }
        } catch (GuzzleException | \Exception $e) {
            return response(sprintf("Error fetching css content - %s", $e->getMessage()))
                ->setStatusCode(400);
        }

        return response()->noContent(404);
    }
}
