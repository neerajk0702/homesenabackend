<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // remove old unique
            $table->dropUnique('users_email_unique');

            // add composite unique
            $table->unique(['email', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // remove composite unique
            $table->dropUnique(['email', 'role']);

            // restore old unique
            $table->unique('email');
        });
    }
};