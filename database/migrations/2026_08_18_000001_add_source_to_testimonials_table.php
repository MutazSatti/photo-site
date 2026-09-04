<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مصدر الرأي: هل وصل مباشرةً إلى المصور أم نُقل من تقييمات Google؟
     *
     * الفرق ليس شكليًا. إرشادات Google للبيانات المهيكلة تشترط أن تصل التقييمات
     * من المستخدمين إلى الموقع مباشرةً، وتمنع تجميعها من مواقع أخرى. لذلك تُستثنى
     * الآراء المنقولة من AggregateRating، بينما تُعرض في الصفحة عاديًا مع شارة
     * توضّح مصدرها — فهي مفيدة للزائر ولنماذج اللغة التي تقرأ نصّ الصفحة.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('source', 20)->default('direct')->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
