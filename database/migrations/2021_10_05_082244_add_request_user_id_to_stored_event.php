<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestUserIdToStoredEvent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stored_events', function (Blueprint $table) {
            $table->string('request_id')->nullable();
            $table->string('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stored_events', function (Blueprint $table) {
            $table->removeColumn('request_id');
            $table->removeColumn('user_id');
        });
    }
}
