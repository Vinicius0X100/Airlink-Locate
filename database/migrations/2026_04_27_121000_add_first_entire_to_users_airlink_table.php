<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_airlink', function (Blueprint $table) {
            if (! Schema::hasColumn('users_airlink', 'airlink_locate_fisrt_entire')) {
                $table->boolean('airlink_locate_fisrt_entire')->default(false)->after('photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_airlink', function (Blueprint $table) {
            if (Schema::hasColumn('users_airlink', 'airlink_locate_fisrt_entire')) {
                $table->dropColumn('airlink_locate_fisrt_entire');
            }
        });
    }
};
