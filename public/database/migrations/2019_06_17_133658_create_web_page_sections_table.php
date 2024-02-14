<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebPageSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_page_sections', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('web_page_id')->unsigned()->nullable();
            $table->integer('web_section_id')->unsigned()->nullable();
            $table->integer('sequence')->nullable();

            $table->foreign('web_page_id')->references('id')->on('web_pages');
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
        Schema::dropIfExists('web_page_sections');
    }
}
