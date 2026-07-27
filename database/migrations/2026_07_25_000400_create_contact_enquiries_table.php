<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contact enquiries — submissions from the shared "Let's Start Your Career
 * Journey" contact form (home + contact pages). Viewed under Leads → Contact
 * Enquiry in the admin panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('looking_for')->nullable();
            $table->string('interest')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
    }
};
