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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('email');
            $table->text('password')->nullable();
            $table->string('google_id')->nullable();
            $table->string('avatar_disk', 50)->nullable();
            $table->text('avatar_path')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE users
            ADD CONSTRAINT ck_users_auth_method
            CHECK (password IS NOT NULL OR google_id IS NOT NULL),
            ADD CONSTRAINT ck_users_avatar_storage
            CHECK ((avatar_disk IS NOT NULL AND avatar_path IS NOT NULL) OR (avatar_disk IS NULL AND avatar_path IS NULL))'
        );

        DB::statement('CREATE UNIQUE INDEX uq_users_email_ci ON users (lower(email))');
        DB::statement('CREATE UNIQUE INDEX uq_users_google_id ON users (google_id) WHERE google_id IS NOT NULL');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
