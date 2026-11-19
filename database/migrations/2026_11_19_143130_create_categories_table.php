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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->nullable()->constrained('groups')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('type', 20);
            $table->timestampsTz();
        });

        DB::statement("
            ALTER TABLE categories
            ADD CONSTRAINT ck_categories_type
            CHECK (type IN ('DEFAULT', 'CUSTOM')),
            ADD CONSTRAINT ck_categories_scope
            CHECK ((type = 'DEFAULT' AND group_id IS NULL) OR (type = 'CUSTOM' AND group_id IS NOT NULL));
        ");

        DB::statement("CREATE UNIQUE INDEX uq_categories_default_name ON categories (lower(name)) WHERE type = 'DEFAULT'");

        DB::statement("CREATE UNIQUE INDEX uq_categories_custom_group_name ON categories (group_id, lower(name)) WHERE type = 'CUSTOM'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
