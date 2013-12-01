<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddTimestamp extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('commit_stats', function (Blueprint $table) {
            $table->timestamp("created_at"); //git relies on sys clock
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('commit_stats', function (Blueprint $table) {
            $table->dropColumn("created_at");
        });
    }

}