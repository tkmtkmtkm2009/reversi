<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GomokuUserStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gomoku_user_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->tinyInteger('progress')->default(0);
            $table->tinyInteger('level')->default(0);
            $table->tinyInteger('turn')->default(0);
            $table->text('board')->nullable();
            $table->timestamps();
            $table->unique('user_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gomoku_user_statuses');
    }
}
