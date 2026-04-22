<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pump_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pump_state_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 30);
            $table->string('action', 30);
            $table->string('mode', 20);
            $table->boolean('from_state')->nullable();
            $table->boolean('to_state')->nullable();
            $table->string('reason', 150)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pump_logs');
    }
};
