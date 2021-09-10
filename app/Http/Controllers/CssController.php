<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;

class CssController extends Controller
{
    public function getScenarioCss($scenarioId)
    {
        $client = new Client();
        $query = ComponentConfiguration::query();
        $query->platforms();
        $query->byScenario($scenarioId);

        $path = $query->first()->configuration['general']['chatbotCssPath'];

        if ($path) {
            return $client->get($path)
                ->getBody()
                ->getContents();
        }

        return "";
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

        return "";
    }
}
