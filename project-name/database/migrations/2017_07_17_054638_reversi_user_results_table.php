<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReversiUserResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reversi_user_results', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->tinyInteger('level');
            $table->integer('win')->default(0);
            $table->integer('lose')->default(0);
            $table->integer('draw')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'level']);
            $table->index(['user_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reversi_user_results');
    }
}
