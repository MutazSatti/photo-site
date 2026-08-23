<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إعدادات الموقع القابلة للتحرير من لوحة التحكم:
     * بيانات التواصل، روابط التواصل الاجتماعي، نصوص الصفحة الرئيسية.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // general | contact | social | seo | home
            $table->string('type')->default('text');     // text | textarea | number | boolean | json | url
            $table->string('label')->nullable();
            $table->string('hint')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
