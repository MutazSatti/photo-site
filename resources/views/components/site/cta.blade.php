@props([
    'title' => 'جاهز لتوثيق مناسبتك؟',
    'description' => null,
    'compact' => false,
])

<section {{ $attributes->class(['px-4 mx-auto max-w-7xl sm:px-6 lg:px-8', 'py-16' => ! $compact, 'py-10' => $compact]) }}>
    <div class="relative overflow-hidden bg-ink-900 rounded-3xl dark:bg-ink-900">

        {{-- حلقة عدسة مجرّدة كخلفية — بلا صور خارجية --}}
        <div class="absolute -translate-y-1/2 pointer-events-none -end-16 top-1/2 opacity-10" aria-hidden="true">
            <svg width="320" height="320" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.4" class="text-brand-300">
                <circle cx="12" cy="12" r="10" />
                <circle cx="12" cy="12" r="6" />
                <path d="m14.31 8 5.74 9.94M9.69 8h11.48M7.38 12l5.74-9.94M9.69 16 3.95 6.06M14.31 16H2.83m13.79-4-5.74 9.94" />
            </svg>
        </div>

        <div class="relative px-6 py-12 sm:px-12 lg:py-16">
            <div class="max-w-2xl">
                <h2 class="text-2xl font-extrabold leading-tight text-white text-balance sm:text-3xl">
                    {{ $title }}
                </h2>

                <p class="mt-4 text-base leading-8 text-ink-300">
                    {{ $description ?: 'أرسل تفاصيل المناسبة — النوع والتاريخ والمكان — ويصلك عرض سعر دقيق خلال وقت قصير. التغطية متاحة في '.implode('، ', array_slice(config('site.service_areas'), 0, 3)).' وبقية مدن المملكة.' }}
                </p>

                <div class="flex flex-wrap gap-3 mt-8">
                    <x-ui.button href="{{ whatsapp_url() }}" variant="whatsapp" size="lg" icon="whatsapp" :navigate="false" target="_blank" rel="noopener">
                        تواصل عبر الواتساب
                    </x-ui.button>

                    <x-ui.button href="{{ route('contact') }}" variant="brand" size="lg" icon="send">
                        أرسل طلب حجز
                    </x-ui.button>

                    <x-ui.button href="tel:{{ config('site.phone') }}" variant="outline" size="lg" icon="phone" :navigate="false"
                        class="text-white border-white/25 hover:bg-white/10">
                        <span dir="ltr">{{ setting('contact_phone', config('site.phone_local')) }}</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</section>
