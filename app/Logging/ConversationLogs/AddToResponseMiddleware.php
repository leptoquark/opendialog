<?php

namespace App\Logging\ConversationLogs;

use Closure;
use Illuminate\Http\Response;

class AddToResponseMiddleware
{
    const MESSAGES = 'messages';
    const LOGS = 'logs';

    public function handle($request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);
        if ($request->path() === 'incoming/webchat') {
            $baseResponse = json_decode($response->getContent(), true);

            $newResponse = [];
            $newResponse[self::MESSAGES] = $baseResponse;

            $resolve = resolve(ConversationLogs::class);
            $newResponse[self::LOGS] = $resolve->messages;

            $response->setContent($newResponse);
        }

        return $response;
    }
}
