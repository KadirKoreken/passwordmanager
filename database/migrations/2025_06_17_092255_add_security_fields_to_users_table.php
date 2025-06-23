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
        Schema::table('users', function (Blueprint $table) {
            // IP tracking alanları
            $table->string('last_login_ip')->nullable()->after('remember_token');
            $table->string('registration_ip')->nullable()->after('last_login_ip');
            $table->timestamp('last_login_at')->nullable()->after('registration_ip');

            // Güvenlik alanları
            $table->integer('failed_login_attempts')->default(0)->after('last_login_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');

            // Two-factor authentication için
            $table->boolean('two_factor_enabled')->default(false)->after('locked_until');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');

            // Audit trail için
            $table->json('login_history')->nullable()->after('two_factor_secret');
            $table->string('password_salt')->nullable()->after('login_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_ip',
                'registration_ip',
                'last_login_at',
                'failed_login_attempts',
                'locked_until',
                'two_factor_enabled',
                'two_factor_secret',
                'login_history',
                'password_salt'
            ]);
        });
    }
};
