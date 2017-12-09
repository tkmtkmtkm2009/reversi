<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GomokuUserResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gomoku_user_results', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->tinyInteger('level');
            $table->tinyInteger('turn');
            $table->integer('win')->default(0);
            $table->integer('lose')->default(0);
            $table->integer('draw')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'level', 'turn']);
            $table->index(['user_id', 'level', 'turn']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gomoku_user_results');
    }
}
