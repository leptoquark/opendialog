<?php

namespace App\Http\Controllers;

use App\Http\Responses\NewUserInitialFrame;
use App\Http\Responses\NewUserIncomingFrame;
use GuzzleHttp\Client;
use Illuminate\Database\Query\Builder;
use OpenDialogAi\Core\Conversation\Events\Intent\MatchingIncomingIntent;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;
use OpenDialogAi\Core\Conversation\Events\User\NewUser;
use OpenDialogAi\Core\Conversation\Facades\ConversationDataClient;
use OpenDialogAi\Core\Conversation\Facades\ScenarioDataClient;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class FrameDataController extends Controller
{
    private string $requestId;

    public function all()
    {
        $this->requestId = EloquentStoredEvent::all()->last()->meta_data['request_id'];
        return $this->getAllEventsForRequest()->get()->map(function (EloquentStoredEvent $event) {
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

    public function handle($frameNo)
    {
        $this->requestId = EloquentStoredEvent::all()->last()->meta_data['request_id'];

        if ($this->wasNewUser()) {
            $totalFrames = $this->calculateTotalFrames();

            if ($frameNo == 1) {
                $response = new NewUserInitialFrame();
                $response->addScenarios(ConversationDataClient::getAllScenarios());
            } else if ($frameNo < $totalFrames) {
                $response = new NewUserIncomingFrame($frameNo - 1);
                $selectedScenarioId = $this->getSelectedScenarioId();
                $response->addScenario(ScenarioDataClient::getFullScenarioGraph($selectedScenarioId));
            } else if ($frameNo == $totalFrames) {
                //
            } else {
                return response()->setStatusCode(404);
            }

            $response->totalFrames = $totalFrames;
            $response->addEvents($this->getAllEventsForRequest()->get());

            return $response->generateResponse();
        }

        return "returning user";
    }

    /**
     * @return Builder
     */
    private function getAllEventsForRequest()
    {
        return EloquentStoredEvent::where('meta_data->request_id', $this->requestId)
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
