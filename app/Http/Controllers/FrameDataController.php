<?php

namespace App\Http\Controllers;

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

        $incomingAvailable = (new IncomingAvailableIntentsFrame())->addEvents($allEvents)->generateResponse();
        $incomingSelection = (new IncomingSelectionFrame())->addEvents($allEvents)->generateResponse();
        $outgoingAvailable = (new OutgoingAvailableIntentsFrame())->addEvents($allEvents)->generateResponse();
        $outgoingSelection = (new OutgoingSelectionFrame())->addEvents($allEvents)->generateResponse();

        $incomingContextEvent = $allEvents->where('event_class', IncomingIntentStateUpdate::class)->first();
        $incomingContextFrame = (new ContextResponse())->setContextEvent($incomingContextEvent);
        $outgoingContextEvent = $allEvents->where('event_class', OutgoingIntentStateUpdate::class)->first();
        $outgoingContextFrame = (new ContextResponse())->setContextEvent($outgoingContextEvent);

        return [
            'incoming_context' => $incomingContextFrame->generateResponse(),
            'outgoing_context' => $outgoingContextFrame->generateResponse(),
            'total_frames' => $totalFrames,
            'frames' => [
                $incomingAvailable,
                $incomingSelection,
                $outgoingAvailable,
                $outgoingSelection
            ]
        ];
    }

    /**
     * @return Builder
     */
    private function getAllEventsForRequest()
    {
        return StoredEvent::where('meta_data->request_id', $this->requestId)
            ->orderBy('meta_data->timestamp');
    }
}
