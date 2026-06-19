<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The listing orders/filters by start time and filters by a lat/lng
     * bounding box. On a 1M+ row table these indexes keep both the date and
     * location filters off a full table scan.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('created_time');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['created_time']);
            $table->dropIndex(['latitude', 'longitude']);
        });
    }
};
