<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course enquiries — submissions from the "Take the First Step" enquiry modal on
 * the course-details page. Each enquiry references its course (nullable so the
 * enquiry survives if the course is later deleted); course_name is a snapshot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('course_name');                 // snapshot of the course name at enquiry time
            $table->string('name');
            $table->string('email');
            $table->string('phone');                       // mobile number
            $table->string('city')->nullable();
            $table->string('career_goal')->nullable();
            $table->text('message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('status')->default('New');      // New | Contacted | Closed
            $table->timestamps();

            $table->index('course_id');
            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enquiries');
    }
};
