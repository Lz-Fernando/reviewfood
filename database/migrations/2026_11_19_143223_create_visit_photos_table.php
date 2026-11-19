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
        Schema::create('visit_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visit_id');
            $table->uuid('uploaded_by_user_id');
            $table->string('disk', 50)->default('photos');
            $table->text('path');
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->timestampsTz();

            $table->foreign(['visit_id', 'uploaded_by_user_id'], 'fk_visit_photos_participant')
                ->references(['visit_id', 'user_id'])
                ->on('visit_participants')
                ->cascadeOnDelete();

            $table->index('visit_id', 'idx_visit_photos_visit');
        });

        DB::statement('
            ALTER TABLE visit_photos
            ADD CONSTRAINT ck_visit_photos_size
            CHECK (size_bytes IS NULL OR size_bytes >= 0)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_photos');
    }
};
