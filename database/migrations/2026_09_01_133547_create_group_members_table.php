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
        Schema::create('group_members', function (Blueprint $table) {
            $table->foreignUuid('group_id')->constrained('groups')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 20)->default('MEMBER');
            $table->string('status', 20)->default('ACTIVE');
            $table->timestampTz('joined_at')->useCurrent();
            $table->timestampTz('left_at')->nullable();

            $table->primary(['group_id', 'user_id']);
        });

        DB::statement("
            ALTER TABLE group_members
            ADD CONSTRAINT group_members_status_check
            CHECK (status IN ('ACTIVE', 'LEFT'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
