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
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestampTz('joined_at')->useCurrent();
            $table->timestampTz('left_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("
            ALTER TABLE group_members
            ADD CONSTRAINT uq_group_members_group_user UNIQUE (group_id, user_id),
            ADD CONSTRAINT ck_group_members_status CHECK (status IN ('ACTIVE', 'ENDED')),
            ADD CONSTRAINT ck_group_members_dates CHECK ((status = 'ACTIVE' AND left_at IS NULL) OR (status = 'ENDED' AND left_at IS NOT NULL))
        ");

        DB::statement("CREATE UNIQUE INDEX uq_group_members_one_active_group ON group_members(user_id) WHERE status = 'ACTIVE';");
        DB::statement("CREATE INDEX idx_group_members_group_status ON group_members(group_id, status);");

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_group_active_member_limit()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.status = 'ACTIVE' THEN
                    PERFORM 1 FROM groups WHERE id = NEW.group_id FOR UPDATE;

                    IF (
                        SELECT COUNT(*)
                        FROM group_members gm
                        WHERE gm.group_id = NEW.group_id
                          AND gm.status = 'ACTIVE'
                          AND gm.id IS DISTINCT FROM NEW.id
                    ) >= 2 THEN
                        RAISE EXCEPTION 'A group may have at most two active members in the MVP';
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER trg_group_active_member_limit
            BEFORE INSERT OR UPDATE OF group_id, status ON group_members
            FOR EACH ROW EXECUTE FUNCTION enforce_group_active_member_limit();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
        DB::statement('DROP FUNCTION IF EXISTS enforce_group_active_member_limit()');
    }
};
