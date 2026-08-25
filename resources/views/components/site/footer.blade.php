@php
    use App\Models\Section;

    $sections = once(fn () => Section::query()
        ->active()
        ->ordered()
        ->with('activeCategories')
        ->get());

    $social = config('site.social');
    $phone = setting('contact_phone', config('site.phone_local'));
    $email = setting('contact_email', config('site.email'));
@endphp

<footer class="border-t bg-ink-50 border-ink-200 dark:border-ink-800 dark:bg-ink-900">
    <div class="px-4 py-14 mx-auto max-w-7xl sm:px-6 lg:px-8">

        <div class="grid gap-10 lg:grid-cols-12">

            {{-- التعريف --}}
            <div class="lg:col-span-4">
                <div class="flex items-center gap-2.5">
                    <x-site.brand-mark :size="36" :icon="20" />
                    <span class="text-base font-extrabold text-ink-900 dark:text-ink-50">{{ config('site.owner_name') }}</span>
                </div>

                <p class="mt-4 max-w-sm text-sm leading-7 text-ink-600 dark:text-ink-400">
                    مصور فوتوغرافي محترف في {{ config('site.location.city') }}. تغطية المناسبات والفعاليات
                    والمؤتمرات والمعارض والتصوير العقاري، إضافة إلى ورش تدريبية في التصوير.
                </p>

                <div class="flex gap-2 mt-6">
                    @foreach ($social as $key => $item)
                        <a
                            href="{{ setting("social_{$key}", $item['url']) }}"
                            target="_blank"
                            rel="noopener me"
                            class="flex items-center justify-center transition-colors border rounded-xl size-10 border-ink-200 bg-white text-ink-600 hover:border-brand-400 hover:text-brand-600 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-400 dark:hover:border-brand-500 dark:hover:text-brand-400"
                            aria-label="{{ $item['label'] }}"
                            title="{{ $item['label'] }} — {{ config('site.handle') }}"
                        >
                            <x-icon :name="$item['icon'] === 'instagram' ? 'instagram-solid' : $item['icon']" :size="18" />
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- الأقسام --}}
            <div class="lg:col-span-2">
                <h2 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">الأقسام</h2>
                <ul class="mt-4 space-y-2.5">
                    <li>
                        <a href="{{ route('portfolio') }}" wire:navigate class="text-sm transition-colors text-ink-600 hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                            المعرض الكامل
                        </a>
                    </li>
                    @foreach ($sections as $section)
                        <li>
                            <a href="{{ $section->url() }}" wire:navigate class="text-sm transition-colors text-ink-600 hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                                {{ $section->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- خدمات التصوير --}}
            <div class="lg:col-span-3">
                <h2 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">خدمات التصوير</h2>
                <ul class="mt-4 space-y-2.5">
                    @foreach ($sections->firstWhere('slug', Section::SERVICES)?->activeCategories ?? [] as $category)
                        <li>
                            <a href="{{ $category->url() }}" wire:navigate class="text-sm transition-colors text-ink-600 hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                                {{ $category->name }} في {{ config('site.location.city') }}
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('faq') }}" wire:navigate class="text-sm transition-colors text-ink-600 hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                            الأسئلة الشائعة
                        </a>
                    </li>
                </ul>
            </div>

            {{-- التواصل --}}
            <div class="lg:col-span-3">
                <h2 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">التواصل والحجز</h2>
                <ul class="mt-4 space-y-3">
                    <li>
                        <a href="tel:{{ config('site.phone') }}" class="flex items-center gap-2.5 text-sm text-ink-600 transition-colors hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                            <x-icon name="phone" :size="16" />
                            <span dir="ltr">{{ $phone }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ whatsapp_url() }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 text-sm text-ink-600 transition-colors hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                            <x-icon name="whatsapp" :size="16" />
                            واتساب — الأسرع للرد
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ $email }}" class="flex items-center gap-2.5 text-sm text-ink-600 transition-colors hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                            <x-icon name="mail" :size="16" />
                            <span dir="ltr">{{ $email }}</span>
                        </a>
                    </li>
                    <li class="flex items-center gap-2.5 text-sm text-ink-600 dark:text-ink-400">
                        <x-icon name="map-pin" :size="16" />
                        {{ config('site.location.city') }}، {{ config('site.location.country_name') }}
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col gap-4 pt-8 mt-12 border-t border-ink-200 sm:flex-row sm:items-center sm:justify-between dark:border-ink-800">
            <p class="text-xs text-ink-500 dark:text-ink-500">
                © {{ date('Y') }} {{ config('site.owner_name') }}. جميع الصور محفوظة الحقوق.
            </p>

            <div class="flex items-center gap-4">
                {{-- مؤشّر حالة المزامنة مع قاعدة بيانات المتصفح --}}
                <x-site.sync-status />

                <a href="{{ route('sitemap') }}" class="text-xs transition-colors text-ink-500 hover:text-brand-600 dark:hover:text-brand-400">
                    خريطة الموقع
                </a>
            </div>
        </div>
    </div>
</footer>
