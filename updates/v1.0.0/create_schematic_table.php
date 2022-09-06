<?php

namespace JaxWilko\Schematic\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateAttributesTable extends Migration
{

    public function up()
    {
        Schema::create('jaxwilko_schematic_schematics', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('schematic_type');
            $table->longText('schematic_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jaxwilko_schematic_schematics');
    }
}
