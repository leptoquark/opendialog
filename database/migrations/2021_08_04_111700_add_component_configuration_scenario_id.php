<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddComponentConfigurationScenarioId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('component_configurations', function (Blueprint $table) {
            $table->string('scenario_id')->default('');
            $table->dropUnique('component_configurations_name_unique');
            $table->unique(['scenario_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('component_configurations', function (Blueprint $table) {
            $table->dropUnique('component_configurations_scenario_id_name_unique');
            $table->unique('name');
            $table->dropColumn('scenario_id');
        });
    }
}
