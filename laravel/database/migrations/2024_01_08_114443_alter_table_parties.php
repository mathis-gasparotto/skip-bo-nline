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
        Schema::table('parties', function (Blueprint $table) {
            $table->foreignId('author_id')->references('id')->on('users');
            $table->string('status')->default('pending');
            $table->dropColumn('finished');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->removeColumn('author_id');
            $table->removeColumn('status');
            $table->boolean('finished')->default(false);
        });
    }
};
