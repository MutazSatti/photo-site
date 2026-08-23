<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * عنصر محتوى واحد — عمل مصوّر، ورشة تدريبية، مقال، أو منشور تعليمي.
     * القسم الذي ينتمي إليه هو ما يحدّد طريقة عرضه.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();

            // تفاصيل اختيارية بحسب نوع القسم
            $table->string('location')->nullable();          // موقع التصوير
            $table->string('client')->nullable();            // الجهة أو العميل
            $table->date('event_date')->nullable();          // تاريخ المناسبة أو الورشة
            $table->decimal('price', 10, 2)->nullable();     // سعر الورشة أو الخدمة
            $table->string('duration')->nullable();          // مدة الورشة
            $table->unsignedSmallInteger('seats')->nullable(); // عدد المقاعد
            $table->unsignedSmallInteger('reading_minutes')->nullable(); // زمن قراءة المقال

            $table->unsignedInteger('views')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('published');  // draft | published
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->json('keywords')->nullable();

            $table->timestamps();

            $table->index(['section_id', 'status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['is_featured', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
