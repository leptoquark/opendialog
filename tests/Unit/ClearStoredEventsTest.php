<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;
use Tests\TestCase;

class ClearStoredEventsTest extends TestCase
{
    public function testDeleteEventsNoUserId()
    {

        $events = [];
        for ($i = 0; $i < 100; $i++) {
             $events[] = [
                 'event_class' => ScenarioRequestReceived::class,
                 'event_properties' => "{}",
                 'meta_data' => "{}",
                 'user_id' => null,
                 'request_id' => uniqid(),
                 'created_at' => now()
             ];
        }

        StoredEvent::withoutEvents(fn () => StoredEvent::insert($events));

        $this->assertDatabaseCount('stored_events', 100);

        Artisan::call('stored-events:clear');

        $this->assertDatabaseCount('stored_events', 0);
    }

    public function testDeleteOldEvents()
    {
        $events = [];
        for ($i = 0; $i < 50; $i++) {
            $events[] = [
                'event_class' => ScenarioRequestReceived::class,
                'event_properties' => "{}",
                'meta_data' => "{}",
                'user_id' => uniqid(),
                'request_id' => uniqid(),
                'created_at' => now()->subHour()
            ];
        }

        for ($i = 0; $i < 50; $i++) {
            $events[] = [
                'event_class' => ScenarioRequestReceived::class,
                'event_properties' => "{}",
                'meta_data' => "{}",
                'user_id' => uniqid(),
                'request_id' => uniqid(),
                'created_at' => now()
            ];
        }

        StoredEvent::withoutEvents(fn () => StoredEvent::insert($events));

        $this->assertDatabaseCount('stored_events', 100);

        Artisan::call('stored-events:clear');

        // 50 should be less than 1 hour old
        $this->assertDatabaseCount('stored_events', 50);
    }
}
