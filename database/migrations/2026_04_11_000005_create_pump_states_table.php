<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pump_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('mode', 20)->default('automatic');
            $table->boolean('is_on')->default(false);
            $table->timestamp('last_changed_at')->nullable();
            $table->string('last_command_source', 30)->nullable();
            $table->string('last_reason', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pump_states');
    }
};
