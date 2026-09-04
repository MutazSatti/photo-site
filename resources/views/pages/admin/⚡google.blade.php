<?php

use App\Models\GoogleConnection;
use App\Models\Testimonial;
use App\Services\GoogleReviewsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'تقييمات Google'])] class extends Component
{
    public string $accountName = '';

    public string $locationName = '';

    public int $minRating = 4;

    public bool $autoPublish = true;

    /** @var array<int, array{name: string, label: string}> */
    public array $accounts = [];

    /** @var array<int, array{name: string, label: string}> */
    public array $locations = [];

    public ?string $error = null;

    public ?string $status = null;

    public function mount(): void
    {
        $this->status = session('google_status');
        $this->error = session('google_error');

        if ($connection = $this->connection) {
            $this->accountName = (string) $connection->account_name;
            $this->locationName = (string) $connection->location_name;
            $this->minRating = $connection->min_rating;
            $this->autoPublish = $connection->auto_publish;
        }
    }

    #[Computed]
    public function connection(): ?GoogleConnection
    {
        return GoogleConnection::current();
    }

    #[Computed]
    public function configured(): bool
    {
        return GoogleReviewsService::configured();
    }

    #[Computed]
    public function importedCount(): int
    {
        return Testimonial::where('source', Testimonial::SOURCE_GOOGLE)->count();
    }

    #[Computed]
    public function publishedCount(): int
    {
        return Testimonial::where('source', Testimonial::SOURCE_GOOGLE)->where('is_active', true)->count();
    }

    /** يجلب الحسابات والبطاقات المتاحة ليختار منها المالك. */
    public function loadChoices(GoogleReviewsService $google): void
    {
        $this->error = null;

        if (! $connection = $this->connection) {
            return;
        }

        try {
            $this->accounts = $google->accounts($connection);

            if ($this->accountName === '' && count($this->accounts) === 1) {
                $this->accountName = $this->accounts[0]['name'];
            }

            if ($this->accountName !== '') {
                $this->locations = $google->locations($connection, $this->accountName);

                if ($this->locationName === '' && count($this->locations) === 1) {
                    $this->locationName = $this->locations[0]['name'];
                }
            }
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function updatedAccountName(GoogleReviewsService $google): void
    {
        $this->locationName = '';
        $this->locations = [];

        if ($this->accountName !== '') {
            $this->loadChoices($google);
        }
    }

    public function saveSettings(): void
    {
        $this->validate([
            'accountName' => ['required', 'string'],
            'locationName' => ['required', 'string'],
            'minRating' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'accountName.required' => 'اختر الحساب.',
            'locationName.required' => 'اختر بطاقة النشاط.',
        ]);

        $label = collect($this->locations)->firstWhere('name', $this->locationName)['label'] ?? null;

        $this->connection?->update([
            'account_name' => $this->accountName,
            'location_name' => $this->locationName,
            'location_title' => $label,
            'min_rating' => $this->minRating,
            'auto_publish' => $this->autoPublish,
        ]);

        unset($this->connection);

        $this->dispatch('notify', message: 'حُفظت إعدادات الاستيراد.');
    }

    public function syncNow(GoogleReviewsService $google): void
    {
        $this->error = null;
        $this->status = null;

        if (! $connection = $this->connection) {
            return;
        }

        try {
            $stats = $google->sync($connection);
        } catch (\Throwable $e) {
            $connection->update(['last_error' => $e->getMessage()]);
            $this->error = $e->getMessage();
            unset($this->connection);

            return;
        }

        $this->status = sprintf(
            'تمت المزامنة — جديد: %d، محدَّث: %d، محذوف: %d، متجاوَز: %d.',
            $stats['imported'],
            $stats['updated'],
            $stats['removed'],
            $stats['skipped'],
        );

        unset($this->connection, $this->importedCount, $this->publishedCount);
    }

    public function disconnect(): void
    {
        $this->connection?->delete();

        cache()->forget('google.access_token');

        $this->reset(['accountName', 'locationName', 'accounts', 'locations']);
        unset($this->connection);

        $this->dispatch('notify', message: 'فُصل حساب Google. التقييمات المستوردة بقيت كما هي.');
    }
}; ?>

<div>
    <x-admin.page-header
        title="تقييمات Google"
        description="استيراد تلقائي يومي لتقييمات بطاقة نشاطك على خرائط Google."
    />

    @if ($error)
        <x-ui.alert variant="danger" class="mb-6">{{ $error }}</x-ui.alert>
    @endif

    @if ($status)
        <x-ui.alert variant="success" class="mb-6">{{ $status }}</x-ui.alert>
    @endif

    {{-- ================= لم تُضبط بيانات التطبيق بعد ================= --}}
    @if (! $this->configured)
        <x-admin.card title="الخطوة الأولى: بيانات تطبيق Google">
            <div class="text-sm leading-8 text-ink-700 dark:text-ink-300">
                <p>
                    الربط يحتاج تطبيقًا على Google Cloud باسمك. الخطوات مرّة واحدة فقط:
                </p>

                <ol class="mt-4 space-y-3 list-decimal ps-5">
                    <li>
                        أنشئ مشروعًا على
                        <a href="https://console.cloud.google.com/" target="_blank" rel="noopener"
                           class="font-bold underline text-brand-600 dark:text-brand-400 underline-offset-4">Google Cloud Console</a>.
                    </li>
                    <li>
                        اطلب الوصول إلى <span class="font-bold">Business Profile APIs</span> عبر نموذج Google،
                        وفعّل بعد الموافقة: <span dir="ltr" class="font-mono text-xs">My Business Account Management API</span>،
                        <span dir="ltr" class="font-mono text-xs">My Business Business Information API</span>،
                        <span dir="ltr" class="font-mono text-xs">Google My Business API</span>.
                    </li>
                    <li>
                        أنشئ <span class="font-bold">OAuth client ID</span> من نوع Web application، وضع رابط
                        الإعادة التالي حرفيًا:
                        <code dir="ltr" class="mt-2 block rounded-lg bg-ink-100 px-3 py-2 font-mono text-xs text-ink-800 dark:bg-ink-800 dark:text-ink-200">{{ route('admin.google.callback') }}</code>
                    </li>
                    <li>
                        ضع المفتاحين في ملف <code dir="ltr" class="font-mono text-xs">.env</code>:
                        <code dir="ltr" class="mt-2 block rounded-lg bg-ink-100 px-3 py-2 font-mono text-xs text-ink-800 dark:bg-ink-800 dark:text-ink-200">GOOGLE_CLIENT_ID=...<br>GOOGLE_CLIENT_SECRET=...</code>
                    </li>
                </ol>

                <p class="mt-4 text-ink-500 dark:text-ink-400">
                    الموافقة على الوصول إلى واجهات Business Profile تأتي من Google وقد تستغرق أيامًا.
                    حتى ذلك الحين يمكنك إضافة الآراء يدويًا من شاشة آراء العملاء.
                </p>
            </div>
        </x-admin.card>

    {{-- ================= مضبوط لكن غير مربوط ================= --}}
    @elseif (! $this->connection)
        <x-admin.card title="اربط حسابك على Google">
            <p class="text-sm leading-8 text-ink-700 dark:text-ink-300">
                ستنتقل إلى شاشة Google لتأذن للموقع بقراءة تقييمات بطاقتك. كلمة مرورك لا تمرّ
                على الموقع إطلاقًا، والصلاحية المطلوبة للقراءة فقط ويمكنك سحبها من حسابك متى شئت.
            </p>

            <div class="mt-5">
                <x-ui.button href="{{ route('admin.google.connect') }}" icon="google">
                    الربط بحساب Google
                </x-ui.button>
            </div>
        </x-admin.card>

    {{-- ================= مربوط ================= --}}
    @else
        <div class="grid gap-6">
            <x-admin.card title="الحساب المربوط">
                <dl class="grid gap-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-ink-500 dark:text-ink-400">الحساب</dt>
                        <dd dir="ltr" class="mt-1 font-bold text-start text-ink-900 dark:text-ink-100">
                            {{ $this->connection->connected_email ?: 'مربوط' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-ink-500 dark:text-ink-400">البطاقة</dt>
                        <dd class="mt-1 font-bold text-ink-900 dark:text-ink-100">
                            {{ $this->connection->location_title ?: 'لم تُختر بعد' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-ink-500 dark:text-ink-400">آخر مزامنة</dt>
                        <dd class="mt-1 font-bold text-ink-900 dark:text-ink-100">
                            {{ $this->connection->last_synced_at?->diffForHumans() ?? 'لم تتم بعد' }}
                        </dd>
                    </div>
                </dl>

                @if ($this->connection->last_error)
                    <x-ui.alert variant="danger" class="mt-5">
                        آخر محاولة فشلت: {{ $this->connection->last_error }}
                    </x-ui.alert>
                @endif

                <div class="flex flex-wrap gap-2 pt-5 mt-5 border-t border-ink-200 dark:border-ink-800">
                    <x-ui.button wire:click="loadChoices" variant="outline" icon="refresh" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="loadChoices">تحديث قائمة البطاقات</span>
                        <span wire:loading wire:target="loadChoices">جارٍ الجلب…</span>
                    </x-ui.button>

                    @if ($this->connection->isReady())
                        <x-ui.button wire:click="syncNow" icon="download" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="syncNow">استورد الآن</span>
                            <span wire:loading wire:target="syncNow">جارٍ الاستيراد…</span>
                        </x-ui.button>
                    @endif

                    <x-ui.button wire:click="disconnect" wire:confirm="فصل حساب Google؟ التقييمات المستوردة ستبقى."
                        variant="ghost" icon="trash" class="text-red-600 dark:text-red-400">
                        فصل الحساب
                    </x-ui.button>
                </div>
            </x-admin.card>

            <x-admin.card title="إعدادات الاستيراد">
                <form wire:submit="saveSettings" class="grid gap-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="الحساب" required :error="$errors->first('accountName')">
                            <x-ui.select wire:model.live="accountName">
                                <option value="">— اختر —</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account['name'] }}">{{ $account['label'] }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="بطاقة النشاط" required :error="$errors->first('locationName')">
                            <x-ui.select wire:model="locationName">
                                <option value="">— اختر —</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location['name'] }}">{{ $location['label'] }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                    </div>

                    @if ($accounts === [])
                        <p class="text-sm text-ink-500 dark:text-ink-400">
                            اضغط «تحديث قائمة البطاقات» أعلاه لجلب حساباتك من Google.
                        </p>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field
                            label="أقلّ تقييم يُنشر تلقائيًا"
                            :error="$errors->first('minRating')"
                            hint="التقييمات الأدنى تُستورد وتبقى مخفية، فتراها في شاشة آراء العملاء دون أن تظهر للزوار."
                        >
                            <x-ui.select wire:model="minRating">
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} نجوم فأعلى</option>
                                @endfor
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="النشر التلقائي" hint="عند الإيقاف يُستورد كل شيء مخفيًا وتنشر أنت ما تختاره.">
                            <label class="inline-flex items-center gap-2 py-2.5 text-sm cursor-pointer text-ink-700 dark:text-ink-300">
                                <input type="checkbox" wire:model="autoPublish"
                                    class="border rounded size-4 border-ink-300 text-brand-500 dark:border-ink-600 dark:bg-ink-800">
                                انشر الجديد المطابق للحد تلقائيًا
                            </label>
                        </x-ui.field>
                    </div>

                    <div>
                        <x-ui.button type="submit" icon="check">حفظ الإعدادات</x-ui.button>
                    </div>
                </form>
            </x-admin.card>

            <x-admin.card title="الوضع الحالي">
                <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-ink-500 dark:text-ink-400">مستورد من Google</dt>
                        <dd class="mt-1 text-2xl font-extrabold text-ink-900 dark:text-ink-100" dir="ltr">{{ $this->importedCount }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-ink-500 dark:text-ink-400">ظاهر منها</dt>
                        <dd class="mt-1 text-2xl font-extrabold text-brand-600 dark:text-brand-400" dir="ltr">{{ $this->publishedCount }}</dd>
                    </div>
                </dl>

                <p class="mt-5 text-sm leading-8 text-ink-600 dark:text-ink-400">
                    المزامنة تعمل تلقائيًا كل يوم. التقييمات المستوردة تُعرض بوسم «من تقييمات Google»
                    ولا تدخل في التقييم الإجمالي المهيكل — لأن Google تمنع تجميع تقييمات مصدرها مواقع أخرى.
                    وإذا حذف صاحب التقييم رأيه من Google، تحذفه المزامنة التالية من الموقع أيضًا.
                </p>
            </x-admin.card>
        </div>
    @endif
</div>
