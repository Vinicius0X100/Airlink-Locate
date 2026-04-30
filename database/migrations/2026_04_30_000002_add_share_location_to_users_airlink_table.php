<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_airlink', function (Blueprint $table) {
            $table->boolean('share_location')->default(true)->index();
        });
    }

    public function down(): void
    {
        Schema::table('users_airlink', function (Blueprint $table) {
            $table->dropColumn('share_location');
        });
    }
};

