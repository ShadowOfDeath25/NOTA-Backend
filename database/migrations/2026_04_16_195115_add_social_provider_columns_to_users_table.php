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
            $table->string('google_id')->nullable()->after('password');
            $table->string('github_id')->nullable()->after('google_id');
            $table->string('facebook_id')->nullable()->after('github_id');
            $table->string('twitter_id')->nullable()->after('facebook_id');
            $table->string('linkedin_id')->nullable()->after('twitter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'github_id',
                'facebook_id',
                'twitter_id',
                'linkedin_id',
            ]);
        });
    }
};
