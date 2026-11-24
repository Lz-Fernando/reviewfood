<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignUuid('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invited_email');
            $table->string('token_hash')->unique();
            $table->string('status', 20)->default('PENDING');
            $table->timestampTz('expires_at');
            $table->timestampTz('responded_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("
            ALTER TABLE group_invitations
            ADD CONSTRAINT ck_group_invitations_status
            CHECK (status IN ('PENDING', 'ACCEPTED', 'DECLINED', 'EXPIRED'))
        ");

        DB::statement("CREATE UNIQUE INDEX uq_group_invitations_pending ON group_invitations (group_id, lower(invited_email)) WHERE status = 'PENDING'");

        DB::statement('CREATE INDEX idx_group_invitations_email_status ON group_invitations (lower(invited_email), status)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_invitations');
    }
};
