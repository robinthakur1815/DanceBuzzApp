<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('purchaser_id')->nullable();
            $table->string('amount')->nullable();
            $table->string('currency')->default('INR');
            $table->longText('order_note')->nullable();
            $table->integer('payment_status')->unsigned()->default(1);
            $table->string('payment_id')->nullable();
            $table->string('payment_auth_token')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
