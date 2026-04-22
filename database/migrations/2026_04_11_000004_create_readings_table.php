<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('soil_moisture', 5, 2);
            $table->decimal('air_temperature', 5, 2);
            $table->decimal('air_humidity', 5, 2);
            $table->string('soil_status', 20);
            $table->string('temperature_status', 20);
            $table->string('humidity_status', 20);
            $table->boolean('pump_state')->default(false);
            $table->timestamp('recorded_at')->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
