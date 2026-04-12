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
        Schema::table('chats', function (Blueprint $table): void {
            $table->timestamp('user_one_review_prompt_dismissed_at')->nullable()->after('user_two_id');
            $table->timestamp('user_two_review_prompt_dismissed_at')->nullable()->after('user_one_review_prompt_dismissed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->dropColumn([
                'user_one_review_prompt_dismissed_at',
                'user_two_review_prompt_dismissed_at',
            ]);
        });
    }
};
