<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * لون القسم — مفتاح من لوحة محدودة في config/site.php لا قيمة لون حرّة.
     *
     * الأقسام الفرعية ترث لون قسمها الرئيسي، فيبقى للقسم هويّة بصرية واحدة
     * عبر صفحاته دون أن يضطر المالك لضبط اللون في مكانين.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('color', 20)->default('brand')->after('icon');
        });

        // ألوان مبدئية تميّز الأقسام الأربعة بعضها عن بعض
        $defaults = [
            'services' => 'brand',
            'workshops' => 'teal',
            'articles' => 'violet',
            'tips' => 'emerald',
        ];

        foreach ($defaults as $slug => $color) {
            DB::table('sections')
                ->where('slug', $slug)
                ->update(['color' => $color]);
        }
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
