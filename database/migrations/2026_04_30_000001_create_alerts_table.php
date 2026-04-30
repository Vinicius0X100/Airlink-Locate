<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('type', 40)->index();
            $table->string('message', 255);
            $table->string('actor_name', 160)->default('');
            $table->string('actor_initials', 4)->default('');
            $table->string('actor_photo', 255)->nullable();
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->string('group_name', 120)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('seen_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};

