<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Circular FK (agencies.owner_id -> users.id, users.agency_id -> agencies.id)
    // is resolved here once both tables exist.
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });
    }
};
