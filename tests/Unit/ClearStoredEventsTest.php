<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use OpenDialogAi\Core\Conversation\Events\Sensor\ScenarioRequestReceived;
use Tests\TestCase;

class ClearStoredEventsTest extends TestCase
{
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

        config('event-sourcing.stored_event_model')::withoutEvents(
            fn () => config('event-sourcing.stored_event_model')::insert($events)
        );

        $this->assertDatabaseCount('stored_events', 100);

        Artisan::call('stored-events:clear');

        // 50 should be less than 1 hour old
        $this->assertDatabaseCount('stored_events', 50);
    }

    public function testTimeToLive()
    {
        Config::set('event-sourcing.time_to_live', 5);

        for ($i = 0; $i < 50; $i++) {
            $events[] = [
                'event_class' => ScenarioRequestReceived::class,
                'event_properties' => "{}",
                'meta_data' => "{}",
                'user_id' => uniqid(),
                'request_id' => uniqid(),
                'created_at' => now()->subMinutes(5)
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

        config('event-sourcing.stored_event_model')::withoutEvents(
            fn () => config('event-sourcing.stored_event_model')::insert($events)
        );

        $this->assertDatabaseCount('stored_events', 100);

        Artisan::call('stored-events:clear');

        // 50 should be less than 5 minutes old
        $this->assertDatabaseCount('stored_events', 50);
    }
}
