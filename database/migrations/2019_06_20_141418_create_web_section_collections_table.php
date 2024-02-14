<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSectionCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_section_collections', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('collection_id')->nullable();
            $table->integer('web_section_id')->unsigned()->nullable();

            $table->foreign('collection_id')->references('id')->on('collections');
            $table->foreign('web_section_id')->references('id')->on('web_sections');

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
        Schema::dropIfExists('web_section_collections');
    }
}
