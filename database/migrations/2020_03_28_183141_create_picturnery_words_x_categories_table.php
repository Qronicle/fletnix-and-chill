<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePicturneryWordsXCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('picturnery_words_x_categories', function (Blueprint $table) {
            $table->bigInteger('category_id')->unsigned()->index();
            $table->foreign('category_id')->references('id')->on('picturnery_word_categories')->onDelete('cascade');
            $table->bigInteger('word_id')->unsigned()->index();
            $table->foreign('word_id')->references('id')->on('picturnery_words')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('picturnery_words_x_categories');
    }
}
