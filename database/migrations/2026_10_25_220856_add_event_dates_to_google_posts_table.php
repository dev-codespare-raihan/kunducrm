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
        Schema::table('google_posts', function (Blueprint $table): void {
            $table->dateTime('event_start_at')->nullable()->after('offer_end_date');
            $table->dateTime('event_end_at')->nullable()->after('event_start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_posts', function (Blueprint $table): void {
            $table->dropColumn(['event_start_at', 'event_end_at']);
        });
    }
};
