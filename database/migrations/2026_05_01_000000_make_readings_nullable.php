<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('readings', function (Blueprint $table) {
            if (Schema::hasColumn('readings', 'air_temperature')) {
                $table->decimal('air_temperature', 8, 2)->nullable()->change();
            }

            if (Schema::hasColumn('readings', 'air_humidity')) {
                $table->decimal('air_humidity', 8, 2)->nullable()->change();
            }

            if (Schema::hasColumn('readings', 'temperature_status')) {
                $table->string('temperature_status')->nullable()->change();
            }

            if (Schema::hasColumn('readings', 'humidity_status')) {
                $table->string('humidity_status')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('readings', function (Blueprint $table) {
            if (Schema::hasColumn('readings', 'air_temperature')) {
                $table->decimal('air_temperature', 8, 2)->nullable(false)->change();
            }

            if (Schema::hasColumn('readings', 'air_humidity')) {
                $table->decimal('air_humidity', 8, 2)->nullable(false)->change();
            }

            if (Schema::hasColumn('readings', 'temperature_status')) {
                $table->string('temperature_status')->nullable(false)->change();
            }

            if (Schema::hasColumn('readings', 'humidity_status')) {
                $table->string('humidity_status')->nullable(false)->change();
            }
        });
    }
};
