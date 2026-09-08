{{-- ================= الجهات والعملاء ================= --}}
@if ($this->clients->isNotEmpty())
    <section data-block="clients" class="px-4 py-16 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-2xl">
            <h2 class="text-2xl font-extrabold text-ink-900 sm:text-3xl dark:text-ink-50">{{ $block->heading() }}</h2>
            <p class="mt-3 text-base leading-8 text-ink-600 dark:text-ink-400">
                {{ $block->intro() }}
            </p>
        </div>

        {{--
            الشعارات تختلف ألوانًا ونِسَبًا، فالتناسق يأتي من الإطار لا من الملفات:
            بطاقة واحدة بارتفاع ثابت لكل شعار، و object-contain يُدرج الشعار داخلها
            كما هو بلا قصّ ولا تشويه، وصنف logo-mark يوحّد معالجته اللونية.

            والبطاقة تبقى فاتحة في الوضع الداكن أيضًا — وحدها من بطاقات الموقع.
            الشعار ليس محتوى نكتبه بل ملف تصميم يملكه صاحبه، مرسوم ليُقرأ على
            خلفية بيضاء؛ فإقحامه على سطح داكن يفسده مهما عولج بالمرشّحات.

            والاسم مكتوب تحت الشعار لا مكتفًى بالشعار وحده: كثير من الشعارات
            رمزية أو بخطّ محوّر لا يُقرأ في مربّع صغير، والزائر يحتاج أن يعرف
            الجهة لا أن يستنتجها. والارتفاع ثابت فتبقى الشبكة مستوية مهما طال اسم.
        --}}
        <ul role="list" class="grid grid-cols-2 gap-4 mt-10 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($this->clients as $client)
                <li>
                    @php($tile = 'group flex h-32 flex-col items-center justify-center gap-2.5 rounded-2xl border border-ink-200 bg-white p-4 transition-all hover:border-brand-300 hover:shadow-lg hover:shadow-black/5 dark:border-ink-300 dark:bg-ink-100 dark:hover:border-brand-500 sm:h-36')

                    @if ($client->url)
                        <a href="{{ $client->url }}" target="_blank" rel="noopener nofollow" class="{{ $tile }}" title="{{ $client->name }}">
                            @include('home.partials.client-logo')
                        </a>
                    @else
                        <div class="{{ $tile }}" title="{{ $client->name }}">
                            @include('home.partials.client-logo')
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
