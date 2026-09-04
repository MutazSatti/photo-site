<?php

use App\Models\HomeBlock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الجهات التي تعامل معها المصوّر — تُعرض شعاراتها في الصفحة الرئيسية.
     *
     * الشعار سجل Media مستقل يشير إليه media_id، لا عمود مسار نصّي، حتى يمرّ
     * من ImageService كبقية صور الموقع فيُحوَّل إلى WebP بعدة مقاسات ويُحذف
     * ملفه من القرص تلقائيًا عند حذفه.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('url')->nullable();          // موقع الجهة — اختياري
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $this->registerHomeBlock();
    }

    public function down(): void
    {
        HomeBlock::where('key', 'clients')->delete();

        Schema::dropIfExists('clients');
    }

    /**
     * يُدرج العنصر في موضعه من الصفحة الرئيسية على قاعدة بيانات تعمل أصلًا.
     *
     * التثبيت الجديد لا يمرّ من هنا: جدول العناصر يُبذر بعد الترحيل من
     * HomeBlock::definitions() وفيه المفتاح بترتيبه الصحيح. أما الموقع المنشور
     * فصفوفه موجودة وترتيبها قرار المالك، فيُدرج المفتاح قبل «آراء العملاء»
     * ويُزاح ما بعده خطوة واحدة — الشعارات ثم الآراء كتلة واحدة تبني الثقة.
     */
    private function registerHomeBlock(): void
    {
        if (! HomeBlock::query()->exists() || HomeBlock::where('key', 'clients')->exists()) {
            return;
        }

        $definition = collect(HomeBlock::definitions())->firstWhere('key', 'clients');

        $position = HomeBlock::where('key', 'testimonials')->value('sort_order')
            ?? ((int) HomeBlock::max('sort_order') + 1);

        HomeBlock::where('sort_order', '>=', $position)->increment('sort_order');

        HomeBlock::create([
            'key' => 'clients',
            'label' => $definition['label'] ?? 'جهات وثقت بعدستي',
            'hint' => $definition['hint'] ?? null,
            'sort_order' => $position,
            'is_active' => true,
        ]);
    }
};
