<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * عناصر الصفحة الرئيسية وترتيبها.
     *
     * الترتيب بيانات لا شيفرة: الصفحة تمرّ على هذا الجدول وتعرض كل عنصر مفعّل
     * حسب موضعه. المفتاح `key` يقابل اسم مكوّن Blade في components/home، فإضافة
     * عنصر جديد مستقبلًا تعني ملفًا جديدًا وصفًا في هذا الجدول لا تعديل الصفحة.
     */
    public function up(): void
    {
        Schema::create('home_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('hint')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // العنصر الأساسي لا يُخفى ولا يُنقل من رأس الصفحة
            $table->boolean('is_locked')->default(false);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_blocks');
    }
};
