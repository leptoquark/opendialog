<?php

namespace App\Providers;

use App\Logging\ConversationLogs\ConversationLogs;
use Illuminate\Support\ServiceProvider;

class ConversationLogProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(ConversationLogs::class, function () {
            return new ConversationLogs();
        });
    }
}
