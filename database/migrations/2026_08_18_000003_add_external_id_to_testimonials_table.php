<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * معرّف الرأي عند مصدره الخارجي.
     *
     * هذا ما يمنع التكرار عند كل مزامنة: المزامنة تبحث عن الرأي بمعرّفه لا بنصّه،
     * فتعديل العميل لتقييمه يُحدّث الصف نفسه بدل أن ينشئ صفًا ثانيًا. يبقى null
     * للآراء التي تُدخل يدويًا، ولذلك الفهرس فريد لكنه يسمح بتعدّد القيم الفارغة.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('external_id')->nullable()->unique()->after('source');
            $table->timestamp('reviewed_at')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn(['external_id', 'reviewed_at']);
        });
    }
};
