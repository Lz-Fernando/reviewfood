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
        Schema::create('visit_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 180);
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->timestampsTz();

            $table->index('visit_id', 'idx_visit_items_visit');
        });

        DB::statement('
            ALTER TABLE visit_items
            ADD CONSTRAINT ck_visit_items_quantity CHECK (quantity > 0),
            ADD CONSTRAINT ck_visit_items_unit_price CHECK (unit_price >= 0)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_items');
    }
};
