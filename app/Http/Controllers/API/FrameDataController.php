<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\IncomingAvailableIntentsFrame;
use App\Http\Responses\ContextResponse;
use App\Http\Responses\IncomingSelectionFrame;
use App\Http\Responses\OutgoingAvailableIntentsFrame;
use App\Http\Responses\OutgoingSelectionFrame;
use Illuminate\Database\Query\Builder;
use OpenDialogAi\Core\Conversation\Events\ConversationalState\IncomingIntentStateUpdate;
use OpenDialogAi\Core\Conversation\Events\ConversationalState\OutgoingIntentStateUpdate;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;

class FrameDataController extends Controller
{
    private string $requestId;

    public function all($requestId)
    {
        $this->requestId = $requestId;
        return $this->getAllEventsForRequest()->get()->map(function (StoredEvent $event) {
            $data = [];
            foreach ($event->event_properties as $key => $value) {
                if (!is_null($value)) {
                    $data[$key] = $value;
                }
            }

            $data['message'] = $event->meta_data['message'];

            return [$event->event_class, $data];
        });
    }

    public function handle($requestId)
    {
        $this->requestId = $requestId;

        $totalFrames = 4;
        $allEvents = $this->getAllEventsForRequest()->get();

        if (!$allEvents->count()) {
            return response("no events", 404);
        }

        $frames = [
            1 => (new IncomingAvailableIntentsFrame())->addEvents($allEvents)->generateResponse(),
            2 => (new IncomingSelectionFrame())->addEvents($allEvents)->generateResponse(),
            3 => (new OutgoingAvailableIntentsFrame())->addEvents($allEvents)->generateResponse(),
            4 => (new OutgoingSelectionFrame())->addEvents($allEvents)->generateResponse()
        ];

        $incomingContextEvent = $allEvents->where('event_class', IncomingIntentStateUpdate::class)->first();
        $incomingContextFrame = (new ContextResponse())->setContextEvent($incomingContextEvent);
        $outgoingContextEvent = $allEvents->where('event_class', OutgoingIntentStateUpdate::class)->first();
        $outgoingContextFrame = (new ContextResponse())->setContextEvent($outgoingContextEvent);

        config('event-sourcing.stored_event_model')::destroy($allEvents->map(fn ($event) => $event->id));

        return [
            'incoming_context' => $incomingContextFrame->generateResponse(),
            'outgoing_context' => $outgoingContextFrame->generateResponse(),
            'total_frames' => $totalFrames,
            'frames' => $frames
        ];
    }

    /**
     * @return Builder
     */
    private function getAllEventsForRequest()
    {
        return config('event-sourcing.stored_event_model')::where('request_id', $this->requestId)
            ->orderBy('meta_data->timestamp');
    }
}
