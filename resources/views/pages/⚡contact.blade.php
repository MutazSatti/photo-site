<?php

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Section;
use App\Support\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|min:3|max:80')]
    public string $name = '';

    #[Validate('required|string|min:9|max:20')]
    public string $phone = '';

    #[Validate('nullable|email|max:120')]
    public string $email = '';

    #[Validate('nullable|string|max:80')]
    public string $service = '';

    #[Validate('nullable|date|after_or_equal:today')]
    public string $eventDate = '';

    #[Validate('required|string|min:10|max:2000')]
    public string $message = '';

    /** حقل فخّ للسبام — يملؤه الروبوت ولا يراه الإنسان. */
    public string $website = '';

    public bool $sent = false;

    public function mount(): void
    {
        $city = config('site.location.city');
        $owner = config('site.owner_name');

        seo()
            ->set(
                title: 'التواصل والحجز',
                description: "تواصل مع {$owner} لحجز تصوير في {$city}. الجوال والواتساب ".config('site.phone_local')." — البريد ".config('site.email').". الرد سريع عبر الواتساب.",
            )
            ->breadcrumbs([['label' => 'التواصل والحجز', 'url' => route('contact')]])
            ->addGraph(Schema::contactPage());
    }

    #[Computed]
    public function serviceOptions(): array
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->whereHas('section', fn ($q) => $q->where('slug', Section::SERVICES))
            ->pluck('name')
            ->all();

        return array_merge($categories, ['ورشة تدريبية', 'خدمة أخرى']);
    }

    public function submit(): void
    {
        // الروبوت يملأ الحقل المخفي — نُظهر نجاحًا صامتًا ولا نحفظ شيئًا
        if ($this->website !== '') {
            $this->sent = true;

            return;
        }

        $validated = $this->validate();

        ContactMessage::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?: null,
            'service' => $validated['service'] ?: null,
            'event_date' => $validated['eventDate'] ?: null,
            'message' => $validated['message'],
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);

        $this->reset(['name', 'phone', 'email', 'service', 'eventDate', 'message']);

        $this->sent = true;
    }
}; ?>

<div>
    <x-site.page-header
        title="التواصل والحجز"
        tagline="الرد خلال وقت قصير"
        :description="setting('contact_note')"
        icon="phone"
        :breadcrumbs="[['label' => 'التواصل والحجز']]"
    />

    <div class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-12">

            {{-- ================= وسائل التواصل ================= --}}
            <div class="lg:col-span-5">
                <h2 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">وسائل التواصل المباشر</h2>

                <div class="mt-6 space-y-3">
                    <a
                        href="{{ whatsapp_url() }}"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-4 p-5 transition-colors border group rounded-2xl border-ink-200 hover:border-[#25D366] hover:bg-ink-50 dark:border-ink-800 dark:hover:bg-ink-900"
                    >
                        <span class="flex items-center justify-center shrink-0 size-11 rounded-xl bg-[#25D366]/10 text-[#25D366]">
                            <x-icon name="whatsapp" :size="21" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-ink-900 dark:text-ink-100">واتساب</p>
                            <p class="text-xs text-ink-500 dark:text-ink-400">الأسرع للرد — أرسل تفاصيل المناسبة مباشرة</p>
                        </div>
                        <span class="text-ink-300 ms-auto dark:text-ink-600"><x-icon name="arrow-left" :size="17" /></span>
                    </a>

                    <a
                        href="tel:{{ config('site.phone') }}"
                        class="flex items-center gap-4 p-5 transition-colors border group rounded-2xl border-ink-200 hover:border-brand-400 hover:bg-ink-50 dark:border-ink-800 dark:hover:border-brand-600 dark:hover:bg-ink-900"
                    >
                        <span class="flex items-center justify-center shrink-0 size-11 rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <x-icon name="phone" :size="20" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-ink-900 dark:text-ink-100">اتصال مباشر</p>
                            <p class="text-xs text-ink-500 dark:text-ink-400" dir="ltr">{{ setting('contact_phone', config('site.phone_local')) }}</p>
                        </div>
                        <span class="text-ink-300 ms-auto dark:text-ink-600"><x-icon name="arrow-left" :size="17" /></span>
                    </a>

                    <a
                        href="mailto:{{ setting('contact_email', config('site.email')) }}"
                        class="flex items-center gap-4 p-5 transition-colors border group rounded-2xl border-ink-200 hover:border-brand-400 hover:bg-ink-50 dark:border-ink-800 dark:hover:border-brand-600 dark:hover:bg-ink-900"
                    >
                        <span class="flex items-center justify-center shrink-0 size-11 rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <x-icon name="mail" :size="20" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-ink-900 dark:text-ink-100">البريد الإلكتروني</p>
                            <p class="text-xs truncate text-ink-500 dark:text-ink-400" dir="ltr">{{ setting('contact_email', config('site.email')) }}</p>
                        </div>
                        <span class="text-ink-300 ms-auto dark:text-ink-600"><x-icon name="arrow-left" :size="17" /></span>
                    </a>
                </div>

                {{-- التواصل الاجتماعي --}}
                <h2 class="mt-10 text-xl font-extrabold text-ink-900 dark:text-ink-50">تابعني</h2>
                <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
                    كل الحسابات على المعرّف نفسه: <span dir="ltr" class="font-bold">{{ config('site.handle') }}</span>
                </p>

                <div class="grid grid-cols-2 gap-3 mt-5">
                    @foreach (config('site.social') as $key => $item)
                        <a
                            href="{{ setting("social_{$key}", $item['url']) }}"
                            target="_blank"
                            rel="noopener me"
                            class="flex items-center gap-3 p-4 transition-colors border rounded-xl border-ink-200 hover:border-brand-400 hover:bg-ink-50 dark:border-ink-800 dark:hover:border-brand-600 dark:hover:bg-ink-900"
                        >
                            <span class="text-ink-600 dark:text-ink-400">
                                <x-icon :name="$item['icon'] === 'instagram' ? 'instagram-solid' : $item['icon']" :size="19" />
                            </span>
                            <span class="text-sm font-bold text-ink-800 dark:text-ink-200">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- الموقع --}}
                <div class="p-5 mt-8 bg-ink-50 rounded-2xl dark:bg-ink-900">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 text-brand-600 dark:text-brand-400"><x-icon name="map-pin" :size="18" /></span>
                        <div>
                            <p class="text-sm font-extrabold text-ink-900 dark:text-ink-100">
                                {{ config('site.location.city') }}، {{ config('site.location.country_name') }}
                            </p>
                            <p class="mt-1 text-xs leading-6 text-ink-500 dark:text-ink-400">
                                التغطية متاحة في {{ implode('، ', config('site.service_areas')) }}.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= نموذج الحجز ================= --}}
            <div class="lg:col-span-7">
                <div class="p-6 border rounded-3xl border-ink-200 sm:p-8 dark:border-ink-800">
                    <h2 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">أرسل طلب حجز</h2>
                    <p class="mt-2 text-sm text-ink-500 dark:text-ink-400">
                        كلما كانت التفاصيل أوضح، وصلك عرض سعر أدقّ وأسرع.
                    </p>

                    @if ($sent)
                        <x-ui.alert variant="success" title="وصلني طلبك" class="mt-6">
                            سأتواصل معك على الرقم الذي أرسلته في أقرب وقت. إن كان الأمر عاجلًا فالواتساب أسرع وسيلة.
                        </x-ui.alert>

                        <div class="flex flex-wrap gap-3 mt-6">
                            <x-ui.button href="{{ whatsapp_url() }}" variant="whatsapp" icon="whatsapp" :navigate="false" target="_blank" rel="noopener">
                                تواصل عبر الواتساب الآن
                            </x-ui.button>
                            <x-ui.button wire:click="$set('sent', false)" variant="ghost">إرسال طلب آخر</x-ui.button>
                        </div>
                    @else
                        <form wire:submit="submit" class="grid gap-5 mt-6">

                            {{-- حقل الفخّ — مخفي عن المستخدم ومقروء للروبوتات --}}
                            <div class="absolute w-px h-px overflow-hidden opacity-0 -z-10" aria-hidden="true">
                                <label for="website">لا تملأ هذا الحقل</label>
                                <input id="website" type="text" wire:model="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-ui.field label="الاسم" for="name" required :error="$errors->first('name')">
                                    <x-ui.input id="name" wire:model="name" autocomplete="name" placeholder="الاسم الكامل" :invalid="$errors->has('name')" />
                                </x-ui.field>

                                <x-ui.field label="رقم الجوال" for="phone" required :error="$errors->first('phone')">
                                    <x-ui.input id="phone" wire:model="phone" type="tel" dir="ltr" autocomplete="tel" placeholder="05xxxxxxxx" :invalid="$errors->has('phone')" />
                                </x-ui.field>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-ui.field label="البريد الإلكتروني" for="email" hint="اختياري" :error="$errors->first('email')">
                                    <x-ui.input id="email" wire:model="email" type="email" dir="ltr" autocomplete="email" placeholder="you@example.com" :invalid="$errors->has('email')" />
                                </x-ui.field>

                                <x-ui.field label="نوع الخدمة" for="service" :error="$errors->first('service')">
                                    <x-ui.select id="service" wire:model="service">
                                        <option value="">اختر نوع الخدمة</option>
                                        @foreach ($this->serviceOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.field>
                            </div>

                            <x-ui.field label="تاريخ المناسبة" for="eventDate" hint="اختياري — يساعد على تأكيد التوفّر" :error="$errors->first('eventDate')">
                                <x-ui.input id="eventDate" wire:model="eventDate" type="date" :invalid="$errors->has('eventDate')" />
                            </x-ui.field>

                            <x-ui.field label="تفاصيل الطلب" for="message" required :error="$errors->first('message')"
                                hint="اذكر نوع المناسبة، المكان، عدد الساعات المتوقّعة، وأي متطلبات خاصة.">
                                <x-ui.textarea id="message" wire:model="message" rows="5"
                                    placeholder="مثال: حفل تخرّج في قاعة بجدة يوم الخميس، التغطية من 5 عصرًا حتى 10 مساءً، ونحتاج صورًا جماعية بعد الحفل."
                                    :invalid="$errors->has('message')" />
                            </x-ui.field>

                            <div class="flex flex-wrap items-center gap-3">
                                <x-ui.button type="submit" variant="primary" size="lg" icon="send" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="submit">إرسال الطلب</span>
                                    <span wire:loading wire:target="submit">جارٍ الإرسال…</span>
                                </x-ui.button>

                                <p class="text-xs text-ink-500 dark:text-ink-400">
                                    بياناتك تُستخدم للرد على طلبك فقط.
                                </p>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
