<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * عنوان ومقدّمة لكل عنصر في الصفحة الرئيسية.
 *
 * كانت مكتوبة في القوالب، فلا يبدّل «أعمال مختارة» أو «اقرأ وتعلّم» إلا من
 * يملك الشيفرة. العمودان يتركانها للمالك.
 *
 * ويبقيان فارغين هنا عمدًا: الفارغ يعني «استعمل المبدئي» المعرَّف في
 * HomeBlock::definitions، فنسخُ النصوص إلى الجدول كان سيجمّدها عند لحظة
 * الترحيل ويقطعها عن أي تحسين لاحق في الشيفرة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_blocks', function (Blueprint $table) {
            $table->string('title')->nullable()->after('hint');
            $table->text('subtitle')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('home_blocks', function (Blueprint $table) {
            $table->dropColumn(['title', 'subtitle']);
        });
    }
};
