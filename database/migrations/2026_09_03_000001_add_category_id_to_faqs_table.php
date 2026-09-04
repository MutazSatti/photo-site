<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط السؤال بقسم فرعي لا بالقسم الرئيسي وحده.
     *
     * صفحة الخدمة صارت تحمل أسئلتها: «كم صورة تحتاج الشقة؟» يخصّ التصوير
     * العقاري لا خدمات التصوير كلها. والعمود اختياري، فالأسئلة القائمة تبقى
     * على حالها وتظهر حيث كانت تظهر — في /faq وصفحة القسم والرئيسية.
     */
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('section_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
