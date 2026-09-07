{{--
    محتوى بطاقة الجهة — يُدرج داخل رابط أو داخل عنصر عادي حسب وجود موقع للجهة،
    فيبقى في ملف واحد بدل نسخته مرتين.

    الاسم يُكتب دائمًا، ومعه الشعار إن وُجد. والجهة بلا شعار تبقى بطاقةً باسمها
    لا مربّعًا رماديًا فارغًا: الاسم وحده معلومة، والمربّع الفارغ عيب ظاهر —
    فيكبر خطّه حينها ليملأ المساحة التي كان الشعار يشغلها.
--}}
@if ($client->logo)
    <x-site.picture
        :media="$client->logo"
        variant="thumb"
        fit="contain"
        sizes="(min-width: 1024px) 200px, (min-width: 640px) 30vw, 45vw"
        class="logo-mark h-12 w-full shrink-0 sm:h-14"
    />
@endif

{{-- البطاقة فاتحة في الوضعين، فلون النص لا يتبدّل معهما --}}
<span class="line-clamp-2 text-center leading-snug text-ink-500 {{ $client->logo ? 'text-[11px] font-bold' : 'text-sm font-extrabold' }}">
    {{ $client->name }}
</span>
