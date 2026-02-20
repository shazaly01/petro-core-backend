<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pumps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('island_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tank_id')->constrained()->cascadeOnDelete(); // ربط مباشر بالخزان

            $table->string('name');
            // تطبيق قاعدتك: الكود عبارة عن أرقام صحيحة كبيرة
            $table->decimal('code', 18, 0)->nullable()->unique();
            $table->string('model')->nullable();

            // عدادات المسدسين (تمثل قراءة الماكينة باللترات)
            $table->decimal('current_counter_1', 10, 2)->default(0);
            $table->decimal('current_counter_2', 10, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pumps');
    }
};
