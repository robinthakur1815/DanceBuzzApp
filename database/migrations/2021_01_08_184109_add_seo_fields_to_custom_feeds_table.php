<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeoFieldsToCustomFeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_feeds', function (Blueprint $table) {
            $table->longText('description')->nullable()->after('title');
            $table->text('slug')->nullable()->after('title');
            $table->json('saved_content')->nullable()->after('title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_feeds', function (Blueprint $table) {
           $table->dropColumn(['description', 'slug', 'saved_content']);
        });
    }
}
