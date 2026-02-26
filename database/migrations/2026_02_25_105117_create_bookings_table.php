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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('expert_id')->constrained('users');
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('address_id')->constrained('addresses');
            $table->dateTime('scheduled_at');
            $table->enum('status', ['PENDING', 'CONFIRMED', 'ONGOING', 'COMPLETED', 'CANCELLED'])->default('PENDING');          
            $table->decimal('total_amount', 10, 2);
            $table->string('otp_code', 6);      
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
