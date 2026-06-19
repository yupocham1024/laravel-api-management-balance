<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('age');
            $table->enum('sex', array('male', 'female', 'other'));
            $table->string('address_prefecture');
            $table->string('address1');
            $table->string('address2')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array('age','sex','address_prefecture','address1','address2'));
        });
    }
};
