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
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visit_id');
            $table->uuid('user_id');
            $table->smallInteger('food')->nullable();
            $table->smallInteger('environment')->nullable();
            $table->smallInteger('service')->nullable();
            $table->smallInteger('value_for_money')->nullable();
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['visit_id', 'user_id'], 'uq_reviews_visit_user');
            $table->foreign(['visit_id', 'user_id'], 'fk_reviews_participant')
                ->references(['visit_id', 'user_id'])
                ->on('visit_participants')
                ->cascadeOnDelete();

            $table->index(['visit_id', 'status'], 'idx_reviews_visit_status');
        });

        DB::statement("
            ALTER TABLE reviews
            ADD CONSTRAINT ck_reviews_status CHECK (status IN ('DRAFT', 'COMPLETED')),
            ADD CONSTRAINT ck_reviews_food CHECK (food BETWEEN 0 AND 10),
            ADD CONSTRAINT ck_reviews_environment CHECK (environment BETWEEN 0 AND 10),
            ADD CONSTRAINT ck_reviews_service CHECK (service BETWEEN 0 AND 10),
            ADD CONSTRAINT ck_reviews_value CHECK (value_for_money BETWEEN 0 AND 10),
            ADD CONSTRAINT ck_reviews_completion CHECK (
                (status = 'DRAFT' AND completed_at IS NULL)
                OR
                (status = 'COMPLETED'
                 AND completed_at IS NOT NULL
                 AND food IS NOT NULL
                 AND environment IS NOT NULL
                 AND service IS NOT NULL
                 AND value_for_money IS NOT NULL)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
