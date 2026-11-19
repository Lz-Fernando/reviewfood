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
        Schema::create('restaurant_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('favorite')->default(false);
            $table->boolean('would_not_return')->default(false);
            $table->timestampsTz();

            $table->unique(['restaurant_id', 'user_id'], 'uq_restaurant_preferences_user');
        });

        DB::statement("
            ALTER TABLE restaurant_preferences
            ADD CONSTRAINT ck_restaurant_preferences_exclusive
            CHECK (NOT (favorite AND would_not_return))
        ");

        DB::statement('CREATE INDEX idx_restaurant_preferences_favorites ON restaurant_preferences (user_id, favorite) WHERE favorite = TRUE');
        DB::statement('CREATE INDEX idx_restaurant_preferences_no_return ON restaurant_preferences (user_id, would_not_return) WHERE would_not_return = TRUE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_preferences');
    }
};
