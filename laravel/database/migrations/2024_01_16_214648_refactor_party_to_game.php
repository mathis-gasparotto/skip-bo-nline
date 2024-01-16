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
        Schema::rename('parties', 'games');
        Schema::rename('party_users', 'game_users');
        Schema::table('game_users', function (Blueprint $table) {
            $table->renameColumn('party_id', 'game_id');
            $table->renameIndex('party_users_user_id_party_id_unique', 'game_users_user_id_game_id_unique');
            $table->renameIndex('party_users_party_id_foreign', 'game_users_game_id_foreign');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('current_party', 'current_game');
            $table->renameIndex('users_current_party_foreign', 'users_current_game_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('games', 'parties');
        Schema::rename('game_users', 'party_users');
        Schema::table('party_users', function (Blueprint $table) {
            $table->renameColumn('game_id', 'party_id');
            $table->renameIndex('game_users_user_id_game_id_unique', 'party_users_user_id_party_id_unique');
            $table->renameIndex('game_users_game_id_foreign', 'party_users_party_id_foreign');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('current_game', 'current_party');
            $table->renameIndex('users_current_game_foreign', 'users_current_party_foreign');
        });
    }
};
