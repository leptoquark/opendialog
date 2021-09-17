<?php

namespace App\Http\Controllers\API;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;

class ExternalCssController
{
    public function getScenarioCss($scenarioId)
    {
        $client = new Client();
        $query = ComponentConfiguration::query()
            ->platforms()
            ->byScenario($scenarioId);

        $path = $query->first()->configuration['general']['chatbotCssPath'];

        if ($path) {
            return $client->get($path)
                ->getBody()
                ->getContents();
        }

        return response()->setStatusCode(404);
    }

    public function getCss(Request $request)
    {
        $client = new Client();
        $path = $request->get('path');

        if ($path) {
            return $client->get($path)
                ->getBody()
                ->getContents();
        }

        return response()->setStatusCode(404);
    }
}