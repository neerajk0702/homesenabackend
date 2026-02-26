<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
              $table->text('image')->nullable()->after('name');
              $table->text('description')->nullable()->after('image');
              $table->decimal('base_price', 10, 2)->nullable()->after('description'); 
              $table->integer('duration_minutes')->nullable()->after('base_price'); 
              $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->after('duration_minutes'); 
              $table->boolean('new_flag')->default(true)->after('status'); //need to check
              });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            //
        });
    }
};
