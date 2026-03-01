<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('test', function (Blueprint $table) {
            $table->increments('id')->comment('Идентификатор');
            $table->string('name', 64)->index('idx_name')->comment('Наименование');
            $table->unsignedInteger('time')->index('idx_time')->comment('Время добавления');
            $table->unsignedTinyInteger('status')->default(0)->index('idx_status')->comment('Статус');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test');
    }
};
