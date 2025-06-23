<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('action'); // login, logout, password_access, password_create, etc.
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->json('details')->nullable(); // JSON olarak ekstra detaylar
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('is_suspicious')->default(false);
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();

            // Index'ler
            $table->index(['user_id', 'action']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['is_suspicious', 'severity']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
