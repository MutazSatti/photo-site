{{--
    محتوى بطاقة الجهة — يُدرج داخل رابط أو داخل عنصر عادي حسب وجود موقع للجهة،
    فيبقى في ملف واحد بدل نسخته مرتين.

    الجهة بلا شعار تُعرض باسمها لا بمربّع رمادي فارغ: الاسم وحده معلومة، والمربّع
    الفارغ عيب ظاهر.
--}}
@if ($client->logo)
    <x-site.picture
        :media="$client->logo"
        variant="thumb"
        fit="contain"
        sizes="(min-width: 1024px) 200px, (min-width: 640px) 30vw, 45vw"
        class="logo-mark size-full"
    />
@else
    {{-- البطاقة فاتحة في الوضعين، فلون النص لا يتبدّل معهما --}}
    <span class="text-sm font-extrabold text-center text-ink-500 line-clamp-2">
        {{ $client->name }}
    </span>
@endif
