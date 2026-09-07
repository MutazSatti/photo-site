{{-- ================= آراء العملاء ================= --}}
@if ($this->testimonials->isNotEmpty())
    <section data-block="testimonials" class="border-y border-ink-200 bg-ink-50 dark:border-ink-800 dark:bg-ink-900">
        <div class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">آراء العملاء</h2>

            {{--
                الآراء تتبدّل من نفسها فلا يحتاج الزائر إلى تحريكها ولا إلى مغادرة
                الصفحة ليقرأ بقيتها.

                كلها في خلية شبكة واحدة متراكبة، والظاهر منها يُحكم بالشفافية لا
                بـdisplay: لو أُخفيت بـdisplay لانكمش ارتفاع القسم عند كل رأي أقصر
                فقفزت الصفحة تحته. بالتراكب يكون الارتفاع ارتفاعَ أطولها ويثبت.

                وقبل أن يعمل Alpine يخفي x-cloak ما عدا الأول، فلا تظهر الآراء
                متراكمة فوق بعضها للحظة. ولو تعطّل Alpine بقي الأول ظاهرًا — رأي
                واحد أفضل من كومة.
            --}}
            <div
                x-data="{
                    index: 0,
                    count: {{ $this->testimonials->count() }},
                    paused: false,
                    timer: null,
                    start() {
                        if (this.count < 2) return;

                        // من يطلب تقليل الحركة لا تُبدَّل عنده تلقائيًا؛ النقاط تبقى تحت يده
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                        this.timer = setInterval(() => {
                            if (! this.paused) this.index = (this.index + 1) % this.count;
                        }, 7000);
                    },
                }"
                x-init="start()"
                x-on:mouseenter="paused = true"
                x-on:mouseleave="paused = false"
                x-on:focusin="paused = true"
                x-on:focusout="paused = false"
                x-on:livewire:navigating.window="clearInterval(timer)"
                class="mt-10"
                role="group"
                aria-roledescription="عرض متتابع"
                aria-label="آراء العملاء"
            >
                <div class="grid">
                    @foreach ($this->testimonials as $i => $testimonial)
                        <figure
                            @if ($i) x-cloak @endif
                            x-bind:class="index === {{ $i }} ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                            x-bind:aria-hidden="index !== {{ $i }}"
                            class="flex flex-col col-start-1 row-start-1 p-6 transition-opacity duration-700 bg-white border rounded-2xl ease-smooth border-ink-200 sm:p-8 dark:border-ink-800 dark:bg-ink-950"
                            aria-roledescription="رأي"
                            aria-label="رأي {{ $i + 1 }} من {{ $this->testimonials->count() }}"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex gap-0.5 text-brand-500" role="img" aria-label="تقييم {{ $testimonial->rating }} من 5">
                                    @for ($star = 0; $star < $testimonial->rating; $star++)
                                        <x-icon name="star-filled" :size="16" />
                                    @endfor
                                </div>

                                {{-- الإفصاح عن المصدر يقوّي المصداقية: الزائر يعرف أن الرأي قابل للتحقق --}}
                                @if ($testimonial->isFromGoogle())
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-ink-500 dark:text-ink-400">
                                        <x-icon name="google" :size="12" />
                                        من تقييمات Google
                                    </span>
                                @endif
                            </div>

                            {{--
                                الارتفاع ارتفاعُ أطول رأي، فالرأي القصير يترك فراغًا
                                أسفله. توسيطه رأسيًا يجعل الفراغ يبدو تصميمًا لا نقصًا.
                            --}}
                            <blockquote class="flex items-center mt-5 text-base leading-9 text-ink-700 grow sm:text-lg dark:text-ink-300">
                                {{ $testimonial->content }}
                            </blockquote>

                            <figcaption class="pt-5 mt-6 border-t border-ink-200 dark:border-ink-800">
                                <span class="block text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $testimonial->name }}</span>
                                @if ($testimonial->role)
                                    <span class="block mt-0.5 text-xs text-ink-500 dark:text-ink-400">{{ $testimonial->role }}</span>
                                @endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                {{-- النقاط تُظهر الموضع وتتيح التنقّل يدويًا لمن أراد — والتبديل التلقائي يتوقّف عند لمسها --}}
                @if ($this->testimonials->count() > 1)
                    <div class="flex justify-center gap-2 mt-6">
                        @foreach ($this->testimonials as $i => $testimonial)
                            <button
                                type="button"
                                x-on:click="index = {{ $i }}"
                                x-bind:aria-current="index === {{ $i }} ? 'true' : 'false'"
                                x-bind:class="index === {{ $i }} ? 'w-6 bg-brand-500' : 'w-2 bg-ink-300 hover:bg-ink-400 dark:bg-ink-700 dark:hover:bg-ink-600'"
                                class="h-2 rounded-full transition-all duration-300 ease-smooth focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                                aria-label="اعرض الرأي {{ $i + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($url = setting('google_reviews_url'))
                <div class="mt-8 text-center">
                    <a
                        href="{{ $url }}"
                        target="_blank"
                        rel="noopener nofollow"
                        class="inline-flex items-center gap-2 text-sm font-bold transition-colors text-ink-700 hover:text-brand-600 dark:text-ink-300 dark:hover:text-brand-400"
                    >
                        <x-icon name="google" :size="15" />
                        شاهد كل التقييمات على Google
                        <x-icon name="arrow-left" :size="15" />
                    </a>
                </div>
            @endif
        </div>
    </section>
@endif
