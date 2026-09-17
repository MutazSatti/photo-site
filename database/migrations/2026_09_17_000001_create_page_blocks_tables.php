<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدولا محتوى الصفحات المصمَّمة.
 *
 * صفحة التصوير العقاري كُتب نصّها في القالب، فكل تعديل فيها يمرّ بمبرمج. وصفحة
 * الزواجات والمناسبات أطول منها وأكثر نصًّا، ولا يصحّ أن يبقى مالك الموقع
 * عاجزًا عن تغيير جملة فيها. فالنصّ ينتقل إلى القاعة: عنصرٌ لكل قسم من
 * الصفحة، وعناصر متكرّرة داخله.
 *
 * وهذا ليس home_blocks بصيغة أعمّ: ذاك يرتّب أقسامًا لكلٍّ منها قالبه ومصدره
 * من القاعدة، وهذا يحمل نصًّا وعناصر متكرّرة لصفحة واحدة مصمَّمة. توحيدهما
 * كان سيجبر أحدهما على شكل الآخر.
 *
 * parent_id يجعل العنصر شجرة لا قائمة: بطاقة «عروسان» في المبدّل تحمل نقاطها
 * الثلاث، وهي عناصر من الشكل نفسه — فلا جدول ثالثًا لكل تداخل جديد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();

            // الصفحة ومفتاح القسم داخلها: العقد بين القاعدة والقالب
            $table->string('page', 40);
            $table->string('key', 40);

            // ما يراه المالك في اللوحة، لا الزائر
            $table->string('label');
            $table->string('hint')->nullable();

            // نصوص الزائر — الفراغ يعني «أعد المبدئي» لا «امحُ العنوان»
            $table->string('title')->nullable();
            $table->string('subtitle', 500)->nullable();
            $table->text('body')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // المثبّت لا يُخفى ولا يُنقل: صفحة بلا واجهة صفحة بلا مدخل
            $table->boolean('is_locked')->default(false);

            $table->timestamps();

            $table->unique(['page', 'key']);
            $table->index(['page', 'sort_order']);
        });

        Schema::create('page_block_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('page_block_id')->constrained()->cascadeOnDelete();

            // العنصر الأب — حذفه يحذف نقاطه، فلا نقاط يتيمة في الجدول
            $table->foreignId('parent_id')->nullable()
                ->constrained('page_block_items')->cascadeOnDelete();

            // مفتاح أيقونة من x-icon، لا ملف ولا رمز تعبيري
            $table->string('icon', 40)->nullable();

            // العنصر في قسم المعرض ليس نصًّا بل إشارة إلى مجموعة أعمال
            // بعينها: المالك يختارها من قائمة لا يكتب رابطها
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title')->nullable();
            $table->string('subtitle', 500)->nullable();
            $table->text('body')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['page_block_id', 'parent_id', 'sort_order'], 'page_block_items_tree_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_block_items');
        Schema::dropIfExists('page_blocks');
    }
};
