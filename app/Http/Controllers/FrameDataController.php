<?php

namespace App\Http\Controllers;

use App\Http\Responses\InitialFrame;
use App\Http\Responses\IncomingSelectionFrame;
use App\Http\Responses\MiddleFrame;
use App\Http\Responses\OutgoingSelectionSelectionFrame;
use Illuminate\Database\Query\Builder;
use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;
use OpenDialogAi\Core\Conversation\Events\User\NewUser;

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

    public function handle($requestId, $frameNo)
    {
        $this->requestId = $requestId;

        $totalFrames = 4;

        if ($frameNo == 1) {
            $response = new InitialFrame();
        } else if ($frameNo == 2) {
            $response = new IncomingSelectionFrame();
        } else if ($frameNo == 3) {
            $response = new MiddleFrame();
        } else {
            $response = new OutgoingSelectionSelectionFrame();
        }

        $response->totalFrames = $totalFrames;
        $response->addEvents($this->getAllEventsForRequest()->get());

        return $response->generateResponse();
    }

    /**
     * @return Builder
     */
    private function getAllEventsForRequest()
    {
        return StoredEvent::where('meta_data->request_id', $this->requestId)
            ->orderBy('meta_data->timestamp');
    }

    /**
     * @return bool
     */
    private function wasNewUser()
    {
        return $this->getAllEventsForRequest()
            ->where('event_class', NewUser::class)
            ->count() > 0;
    }

    private function calculateTotalFrames()
    {
        return $this->getAllEventsForRequest()
            ->where('event_class', MatchingIncomingIntent::class)
            ->count() + 2;
    }

    private function getSelectedScenarioId()
    {
        $scenarioRequest = $this->getAllEventsForRequest()
            ->where('event_class', ScenarioRequestReceived::class)
            ->first();

        return $scenarioRequest ? $scenarioRequest->event_properties['scenarioId'] : null;
    }
}
