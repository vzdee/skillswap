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
            $table->timestamp('skills_onboarding_completed_at')->nullable()->after('career');
            $table->timestamp('availability_onboarding_completed_at')->nullable()->after('skills_onboarding_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['skills_onboarding_completed_at', 'availability_onboarding_completed_at']);
        });
    }
};
