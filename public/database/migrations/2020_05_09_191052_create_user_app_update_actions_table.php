<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAppUpdateActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_app_update_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('version');
            $table->unsignedTinyInteger('app_type');
            $table->string('device_id');
            $table->unsignedTinyInteger('device_type');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_app_update_actions');
    }
}
