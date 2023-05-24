<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('testing', function (Blueprint $blueprint) {
            $blueprint->string('text');
            $blueprint->integer('number');
        });
    }
};
