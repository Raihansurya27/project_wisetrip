<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('destination_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('destination_id');
            $table->string('title');
            $table->string('review');
            $table->integer('rating')->unsigned();
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
        Schema::dropIfExists('destination_reviews');
    }
};
