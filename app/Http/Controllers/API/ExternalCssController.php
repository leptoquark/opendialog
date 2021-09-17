<?php

namespace App\Http\Controllers\API;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ExternalCssController
{
    public function getCss(Request $request)
    {
        $client = new Client();
        try {
            $path = $request->get('path');

            if ($path) {
                return $client->get($path)
                    ->getBody()
                    ->getContents();
            }
        } catch (\Exception $e) {
            return response()
                ->setContent(sprintf("Error fetching css content - %s", $e->getMessage()))
                ->setStatusCode(400);
        }

        return response()->setStatusCode(404);
    }
}