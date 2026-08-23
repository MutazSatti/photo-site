<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * كل صورة تُرفع تُحوَّل إلى WebP بعدة مقاسات.
     * العمود variants يحمل مسارات المقاسات: thumb / md / lg / full.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->cascadeOnDelete();

            // للصور غير المرتبطة بعنصر — مثل صورة الغلاف الرئيسية أو صورة "نبذة عني"
            $table->string('usage')->nullable();

            $table->string('disk')->default('public');
            $table->string('path');                  // مسار النسخة الكاملة بصيغة webp
            $table->json('variants')->nullable();    // {"thumb":"...","md":"...","lg":"..."}

            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedInteger('size')->nullable();   // بالبايت بعد التحويل
            $table->string('original_name')->nullable();

            $table->string('alt')->nullable();       // النص البديل — مهم للسيو والوصول
            $table->string('caption')->nullable();

            $table->boolean('is_cover')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['post_id', 'sort_order']);
            $table->index('usage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
