<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;

class ClearStoredEvents extends Command
{
    protected $signature = 'stored-events:clear {--y|yes}';

    protected $description = 'Clears out all stored events that either have no user id or all up until the most recent request for
                              events that have a user id';

    public function handle()
    {
        $timeToLive = config('event-sourcing.time_to_live');

        $confirmPreparationText = sprintf(
            "Are you sure you want to prepare stored events older than %d minutes for deletion?",
            $timeToLive
        );

        if (!$this->option("yes") && !$this->confirm($confirmPreparationText)) {
            return;
        }

        $this->info(sprintf("Deleting all events older than %d minutes", $timeToLive));

        $deleted = config('event-sourcing.stored_event_model')::where('created_at', '<=', now()
            ->subMinutes($timeToLive))
            ->delete();

        $this->info(sprintf("Deleted %d events", $deleted));

        Log::info(sprintf("stored-events:clear ran and deleted %d events from the DB", $deleted));
    }
}
