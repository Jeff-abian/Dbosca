<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

public function up()
{
    Schema::create('benefit_annual_cash_gift_applications', function (Blueprint $table) {
        $table->id();
        $table->integer('citizen_id');
        $table->string('first_name');
        $table->string('middle_name')->nullable();
        $table->string('last_name');
        $table->date('birth_date');
        $table->integer('age');
        $table->string('contact_number');
        $table->string('barangay');
        $table->string('city_municipality');
        $table->string('province');
        $table->string('scid_number');
        
        // File Paths
        $table->string('birth_certificate')->nullable();
        $table->string('barangay_certificate')->nullable();
        $table->string('photo')->nullable();

        $table->string('reg_status')->default('Pending');
        $table->timestamps(); // Ito na ang created_at at updated_at
    });
}