<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('manager_id');
            $table->string('name');
            $table->string('email');
            $table->string('location');
            $table->string('category');
            $table->integer('employees_count');
            $table->year('creation_year');
            $table->string('phone');
            $table->string('logo')->nullable();
            $table->string('qr_code')->nullable();
            $table->timestamps();
            
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};