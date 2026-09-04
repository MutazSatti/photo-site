<?php

use App\Models\Setting;
use App\Support\Seo;

if (! function_exists('seo')) {
    /**
     * حامل بيانات السيو للصفحة الحالية.
     * يُستدعى من mount() في صفحات Livewire ومن قالب <head>.
     */
    function seo(): Seo
    {
        return app(Seo::class);
    }
}

if (! function_exists('setting')) {
    /**
     * قيمة إعداد من لوحة التحكم، مع قيمة افتراضية عند غيابها.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('whatsapp_url')) {
    /**
     * رابط محادثة واتساب مع رسالة جاهزة — يقلّل احتكاك التواصل كثيرًا.
     */
    function whatsapp_url(?string $message = null): string
    {
        $number = setting('contact_whatsapp', config('site.whatsapp'));

        $message ??= 'السلام عليكم، وصلت من موقعك وأرغب في الاستفسار عن خدمات التصوير.';

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}

if (! function_exists('accreditations')) {
    /**
     * الاعتمادات الرسمية بأوصافها جاهزةً للعرض.
     *
     * ‎:owner‎ يُستبدل هنا لا في الملف، فيبقى اسم المالك في مصدر واحد
     * (config/site.php أو متغيّر البيئة) ولا يُكتب ثلاث مرات في الأوصاف.
     *
     * @return array<int, array<string, string>>
     */
    function accreditations(): array
    {
        $owner = config('site.owner_name');

        /** @var array<int, array<string, string>> $items */
        $items = config('site.accreditations', []);

        return array_map(function (array $a) use ($owner): array {
            if (isset($a['description'])) {
                $a['description'] = str_replace(':owner', (string) $owner, $a['description']);
            }

            return $a;
        }, $items);
    }
}
