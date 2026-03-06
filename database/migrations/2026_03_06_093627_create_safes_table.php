<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('الخزينة العامة');
            $table->decimal('balance', 18, 3)->default(0); // الرصيد المالي
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safes');
    }
};
