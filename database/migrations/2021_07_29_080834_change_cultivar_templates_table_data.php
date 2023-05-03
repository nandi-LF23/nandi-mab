<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCultivarTemplatesTableData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $oldText = 'idcultivars';
        $newText = 'id';

        DB::update('UPDATE cultivars_templates SET template = REPLACE(template, ?, ?) WHERE template LIKE ?', [ $oldText, $newText, $oldText ] );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $oldText = 'id';
        $newText = 'idcultivars';

        DB::update('UPDATE cultivars_templates SET template = REPLACE(template, ?, ?) WHERE template LIKE ?', [ $oldText, $newText, $oldText ] );

    }
}
