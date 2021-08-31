<?php

namespace App\Console\Commands\ConfigurationUpdates;

use Exception;

interface ConfigurationUpdate
{
    /**
     * Determines whether the update has previously been run, or not.
     *
     * @return bool
     */
    public function hasRun(): bool;

    /**
     * Updates the configuration(s)
     * @throws Exception
     */
    public function up(): void;

    /**
     * Undoes the configuration update(s)
     * @throws Exception
     */
    public function down(): void;
}
