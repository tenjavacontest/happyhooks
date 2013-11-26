<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class InitialMigration extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('commit_stats', function (Blueprint $table) {
            $table->string("repository");
            $table->string("username");
            $table->string("commit_id", 40);
            $table->integer("new_files");
            $table->integer("changed_files");
            $table->integer("removed_files");
            $table->integer("total_deletions");
            $table->integer("total_additions");
            $table->string("commit_message"); //255 default
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('commit_stats');
    }

}