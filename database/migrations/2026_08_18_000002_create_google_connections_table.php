<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط الموقع بحساب Google Business Profile الخاص بالمصور.
     *
     * صف واحد فقط: الموقع لمصور واحد وبطاقة نشاط واحدة. رمز التحديث يُخزَّن
     * مشفّرًا لأنه يمنح وصولًا دائمًا إلى حساب صاحب الموقع على Google.
     */
    public function up(): void
    {
        Schema::create('google_connections', function (Blueprint $table) {
            $table->id();

            $table->text('refresh_token');                       // مشفّر عبر cast
            $table->string('connected_email')->nullable();       // الحساب الذي أذِن

            $table->string('account_name')->nullable();          // accounts/123456
            $table->string('location_name')->nullable();         // locations/987654
            $table->string('location_title')->nullable();        // الاسم المقروء للبطاقة

            // إعدادات الاستيراد
            $table->unsignedTinyInteger('min_rating')->default(4);
            $table->boolean('auto_publish')->default(true);

            // حالة آخر مزامنة
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedSmallInteger('last_imported')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_connections');
    }
};
