<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->create('companies', function (Blueprint $table) {
            $table->id();
            $table->text('company_name');
            $table->text('company_logo')->nullable();

            $table->text('address_billing_line_1')->nullable();
            $table->text('address_billing_line_2')->nullable();
            $table->text('address_billing_city')->nullable();
            $table->text('address_billing_postalcode')->nullable();
            $table->text('address_billing_country')->nullable();

            $table->text('address_physical_line_1')->nullable();
            $table->text('address_physical_line_2')->nullable();
            $table->text('address_physical_city')->nullable();
            $table->text('address_physical_postalcode')->nullable();
            $table->text('address_physical_country')->nullable();

            $table->text('contact_name')->nullable();
            $table->text('contact_email')->nullable();
            $table->text('contact_phone')->nullable();
        });

        DB::connection('mysql_root')->table('companies')->insert([
            [ 'company_name' => 'LF SA', 'contact_name' => 'Dave' ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->dropIfExists('companies');
    }
}
