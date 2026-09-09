<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * لون مخصّص للقسم، خارج الثمانية الجاهزة.
 *
 * لونان لا لون واحد: الموقع فاتح وداكن، ولونٌ يقرأ على الأبيض قد يختفي على
 * الأسود. الحقلان يبقيان فارغين لكل قسم يكتفي بلون من اللوحة الجاهزة، فيبقى
 * عمود color هو المرجع ما لم يُملآ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('color_light', 7)->nullable()->after('color');
            $table->string('color_dark', 7)->nullable()->after('color_light');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['color_light', 'color_dark']);
        });
    }
};
