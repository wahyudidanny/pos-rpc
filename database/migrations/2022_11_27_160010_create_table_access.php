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
        Schema::create('tableaccess', function (Blueprint $table) {
            $table->id();
            $table->integer('menuListId');
            $table->integer('roleId');
            $table->integer('roleAccessId');
            $table->integer('accessLimitId');
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
        Schema::dropIfExists('tableaccess');
    }
};
