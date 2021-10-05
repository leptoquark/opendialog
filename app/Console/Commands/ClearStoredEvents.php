<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenDialogAi\Core\Conversation\Events\Storage\StoredEvent;

class ClearStoredEvents extends Command
{
    protected $signature = 'stored-events:clear';

    protected $description = 'Clears out all stored events that either have no user id or all up until the most recent request for
                              events that have a user id';


    public function handle()
    {
        $this->info("Deleting all stored events with no user ID");

        $deleted = StoredEvent::whereNull('user_id')->delete();

        $this->info(sprintf("Deleted %d rows", $deleted));

        $this->info("Deleting all events older than 1 hour");

        $deleted = StoredEvent::where('created_at', '<=', now()->subHour())->delete();

        $this->info(sprintf("Deleted %d events", $deleted));
    }
}
