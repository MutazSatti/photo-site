<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $heroCta = Setting::where('key', 'hero_cta')->first();
        if ($heroCta && str_contains((string) $heroCta->value, '@')) {
            $heroCta->update(['value' => 'احجز موعد تصوير']);
            Setting::flush();
        }
    }

    public function down(): void
    {
        //
    }
};
