<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120)->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE groups
            ADD CONSTRAINT groups_status_check
            CHECK (status IN ('ACTIVE', 'INACTIVE'));
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
