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
//        Schema::create('party_users', function (Blueprint $table) {
//            $table->engine = 'InnoDB';
//            $table->id();
//            $table->unsignedBigInteger('user_id');
//            $table->string('party_id');
//            $table->string('deck');
//            $table->string('hand');
//            $table->integer('card_draw_count');
//            $table->string('card_draw')->nullable();
//            $table->boolean('win')->nullable();
//            $table->timestamps();
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_users');
    }
};
