<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->index();
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('make');
            $table->string('model');
            $table->string('category')->index();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('seats')->default(4);
            $table->decimal('daily_rate', 10, 2);
            $table->string('status')->default('available')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->index();
            $table->string('email')->nullable();
            $table->string('source')->default('other')->index();
            $table->string('status')->default('new')->index();
            $table->string('trip_type')->nullable();
            $table->string('vehicle_category')->nullable();
            $table->string('pickup_location')->nullable();
            $table->string('destination')->nullable();
            $table->dateTime('pickup_at')->nullable()->index();
            $table->dateTime('return_at')->nullable();
            $table->unsignedTinyInteger('passengers')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('quote')->index();
            $table->string('trip_type')->default('local');
            $table->string('pickup_location');
            $table->string('destination')->nullable();
            $table->dateTime('pickup_at')->index();
            $table->dateTime('return_at')->index();
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('google_business_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('connected_email')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('account_resource')->nullable();
            $table->string('location_resource')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('business_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('business_name')->default('Kundu Tour and Travels');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->json('regular_hours')->nullable();
            $table->json('special_hours')->nullable();
            $table->json('services')->nullable();
            $table->timestamps();
        });

        Schema::create('google_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('summary');
            $table->string('post_type')->default('standard')->index();
            $table->string('call_to_action')->nullable();
            $table->string('call_to_action_url')->nullable();
            $table->string('coupon_code')->nullable();
            $table->date('offer_start_date')->nullable();
            $table->date('offer_end_date')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('google_resource_name')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('google_synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('google_reviews', function (Blueprint $table): void {
            $table->id();
            $table->string('google_resource_name')->unique();
            $table->string('reviewer_name')->nullable();
            $table->string('star_rating')->nullable()->index();
            $table->text('comment')->nullable();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('google_synced_at')->index();
            $table->timestamps();
        });

        Schema::create('google_media', function (Blueprint $table): void {
            $table->id();
            $table->string('google_resource_name')->unique();
            $table->string('category')->default('car')->index();
            $table->string('media_format')->nullable();
            $table->string('google_url')->nullable();
            $table->string('local_path')->nullable();
            $table->timestamp('google_synced_at')->index();
            $table->timestamps();
        });

        Schema::create('business_faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('business_faqs');
        Schema::dropIfExists('google_media');
        Schema::dropIfExists('google_reviews');
        Schema::dropIfExists('google_posts');
        Schema::dropIfExists('business_profiles');
        Schema::dropIfExists('google_business_connections');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('customers');
    }
};
