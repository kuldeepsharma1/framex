<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('tokenable_id')->constrained()->nullOnDelete();
            $table->string('last_ip_address', 45)->nullable()->after('last_used_at');
            $table->text('last_user_agent')->nullable()->after('last_ip_address');
        });

        Schema::create('token_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained('personal_access_tokens')->cascadeOnDelete();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->integer('response_code');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_activity_logs');

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Team::class);
            $table->dropColumn(['team_id', 'last_ip_address', 'last_user_agent']);
        });
    }
};
