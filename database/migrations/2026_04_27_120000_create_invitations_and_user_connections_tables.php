<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->enum('type', ['family', 'circle', 'connection'])->index();
            $table->unsignedBigInteger('inviter_user_id')->index();
            $table->unsignedBigInteger('invitee_user_id')->nullable()->index();
            $table->string('invitee_email')->nullable()->index();
            $table->unsignedBigInteger('family_id')->nullable()->index();
            $table->unsignedBigInteger('circle_id')->nullable()->index();
            $table->enum('status', ['pending', 'accepted', 'declined', 'revoked', 'expired'])->default('pending')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_a_id')->index();
            $table->unsignedBigInteger('user_b_id')->index();
            $table->unsignedBigInteger('requested_by')->index();
            $table->enum('status', ['pending', 'accepted', 'declined', 'blocked'])->default('pending')->index();
            $table->boolean('share_location')->default(true);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_a_id', 'user_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_connections');
        Schema::dropIfExists('invitations');
    }
};
