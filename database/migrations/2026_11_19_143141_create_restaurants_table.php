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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->restrictOnDelete();
            $table->foreignUuid('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 180);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('cover_photo_disk', 50)->nullable();
            $table->text('cover_photo_path')->nullable();
            $table->string('status', 20)->default('TO_VISIT');
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("
            ALTER TABLE restaurants
            ADD CONSTRAINT ck_restaurants_status
            CHECK (status IN ('TO_VISIT', 'VISITED')),
            ADD CONSTRAINT ck_restaurants_cover_storage
            CHECK ((cover_photo_disk IS NULL AND cover_photo_path IS NULL) OR (cover_photo_disk IS NOT NULL AND cover_photo_path IS NOT NULL));
        ");

        DB::statement('CREATE INDEX idx_restaurants_group_status_active ON restaurants (group_id, status) WHERE archived_at IS NULL');
        DB::statement('CREATE INDEX idx_restaurants_category ON restaurants (category_id)');
        DB::statement('CREATE INDEX idx_restaurants_name_ci ON restaurants (lower(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
