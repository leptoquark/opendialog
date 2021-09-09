<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;

class CssController extends Controller
{
    public function getCss($scenarioId)
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
}
