<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('channel', 32);
            $table->string('message', 500);
            $table->string('status', 32)->default('processing');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
