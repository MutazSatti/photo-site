<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * قسم من صفحة مصمَّمة، نصُّه وترتيبه وظهوره من لوحة التحكم.
 *
 * التعريفات أدناه هي ما كان مكتوبًا في القالب قبل أن يصير قابلًا للتحرير،
 * فتبقى الافتراضَ الذي يعود إليه القسم حين يُفرَّغ حقله من اللوحة — لا فراغًا
 * في مكان عنوان. وهي كذلك مصدر البذرة الأولى، فلا يُكتب النصّ مرّتين.
 *
 * ‎:city‎ و‎:owner‎ يُستبدلان عند العرض، فيتبع النصّ إعدادات الموقع ولا يتجمّد
 * على مدينة كُتبت مرة.
 *
 * @property int $id
 * @property string $page
 * @property string $key
 * @property string $label
 * @property string|null $hint
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $body
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_locked
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read EloquentCollection<int, PageBlockItem> $items
 */
class PageBlock extends Model
{
    protected $guarded = [];

    /** صفحة الزواجات والمناسبات — المفتاح المستعمل في page. */
    public const EVENTS = 'events';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<PageBlockItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PageBlockItem::class)->whereNull('parent_id');
    }

    /** @return HasMany<PageBlockItem, $this> */
    public function allItems(): HasMany
    {
        return $this->hasMany(PageBlockItem::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOnPage(Builder $query, string $page): Builder
    {
        return $query->where('page', $page);
    }

    /**
     * الصفحات التي تُحرَّر من اللوحة، بأسمائها كما تظهر فيها.
     *
     * @return array<string, string>
     */
    public static function pages(): array
    {
        return [self::EVENTS => 'الزواجات والمناسبات'];
    }

    /**
     * أقسام صفحةٍ بترتيبها ونصّها المبدئي — المرجع الوحيد لهما.
     *
     * fields تسمّي الحقول الثلاثة بلغة القسم نفسه: «العنوان» في قسم وسطره
     * العلوي في آخر. اسمٌ عامّ كـ«العنوان الفرعي» يترك المالك يخمّن أين يظهر
     * ما يكتبه.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(string $page): array
    {
        return match ($page) {
            self::EVENTS => self::eventsDefinitions(),
            default => [],
        };
    }

    /**
     * أقسام صفحة الزواجات والمناسبات.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function eventsDefinitions(): array
    {
        return [
            [
                'key' => 'hero',
                'label' => 'الواجهة',
                'hint' => 'الصورة الكبيرة والعنوان وأزرار التواصل',
                'locked' => true,
                'title' => 'الزواجات والمناسبات',
                'subtitle' => 'تصوير المناسبات — :city',
                'body' => 'من الاستقبال إلى الصور الجماعية. تغطية متّصلة لا لقطات متفرّقة، وبورتريه مرتَّب بإضاءة مضبوطة.',
                'fields' => [
                    'title' => 'العنوان الكبير',
                    'subtitle' => 'السطر الصغير فوقه',
                    'body' => 'الفقرة تحت العنوان',
                ],
                'image' => 'ev_hero',
            ],
            [
                'key' => 'figures',
                'label' => 'شريط الأرقام',
                'hint' => 'سطر قصير أسفل الواجهة — رقمان أو ثلاثة لا أكثر',
                'fields' => [],
                'item_label' => 'رقم',
                'item_fields' => [
                    'icon' => 'الأيقونة',
                    'title' => 'الرقم أو الكلمة الكبيرة',
                    'subtitle' => 'السطر تحته',
                ],
                'items' => [
                    ['icon' => 'rings', 'title' => '+100', 'subtitle' => 'عريس'],
                    ['icon' => 'camera', 'title' => 'القسم الرجالي', 'subtitle' => 'نطاق التغطية'],
                ],
            ],
            [
                'key' => 'tracks',
                'label' => 'ما المناسبة؟',
                'hint' => 'مبدّل يعرض لكل زائر جوابه وحده',
                'title' => 'ما المناسبة؟',
                'subtitle' => 'لكل مناسبة سؤالها. اختر ما يخصّك ليظهر جوابه وحده.',
                'fields' => ['title' => 'العنوان', 'subtitle' => 'السطر تحته'],
                'item_label' => 'مسار',
                'item_fields' => [
                    'icon' => 'الأيقونة',
                    'title' => 'اسم الزرّ',
                    'subtitle' => 'السطر الصغير في الزرّ',
                    'body' => 'الفقرة داخل المسار',
                ],
                'child_label' => 'نقطة',
                'child_fields' => ['title' => 'النقطة', 'subtitle' => 'شرحها'],
                'items' => [
                    [
                        'icon' => 'rings',
                        'title' => 'عروسان',
                        'subtitle' => 'ليلة زواج',
                        'body' => 'ليلة واحدة لا تُعاد، وفيها لحظات تمرّ في ثوانٍ: الاستقبال، والزفّة، والصور الجماعية. الحضور المبكر وترتيب اللقطات مسبقًا هو ما يجعلها تُلتقط.',
                        'children' => [
                            ['title' => 'من الاستقبال إلى الصور الجماعية', 'subtitle' => 'تغطية متّصلة لا لقطات متفرّقة.'],
                            ['title' => 'بورتريه مرتَّب', 'subtitle' => 'ركن ثابت بإضاءة مضبوطة، لا صور عابرة بين المصافحات.'],
                            ['title' => 'الزفّة', 'subtitle' => 'لحظات تمرّ في ثوانٍ، وتُصوَّر مرّة واحدة.'],
                        ],
                    ],
                    [
                        'icon' => 'academic',
                        'title' => 'عائلة أو مدرسة',
                        'subtitle' => 'تخرّج · تكريم · معايدة',
                        'body' => 'الحفل المدرسي والمعايدة يشتركان في شيء: الناس كثيرون والوقت قصير. والاتفاق على قائمة الصور قبل الحفل يمنع اكتشاف النقص بعد انتهائه.',
                        'children' => [
                            ['title' => 'قائمة لقطات متّفق عليها', 'subtitle' => 'قبل الحفل لا بعده.'],
                            ['title' => 'المنصّة والجمهور معًا', 'subtitle' => 'التكريم واللحظة وردّ الفعل.'],
                            ['title' => 'الشعار والخلفية في الإطار', 'subtitle' => 'صور تصلح لموقع المدرسة وحساباتها بلا قصّ.'],
                        ],
                    ],
                    [
                        'icon' => 'building',
                        'title' => 'جهة أو شركة',
                        'subtitle' => 'افتتاح · مؤتمر · جمعية',
                        'body' => 'الافتتاح والمؤتمر يُصوَّران للنشر لا للأرشيف: صور المنصّة والشعار والضيوف مكانها الحساب الرسمي والصحافة.',
                        'children' => [
                            ['title' => 'الشعار حاضر في الإطار', 'subtitle' => 'صور تصلح للنشر الرسمي بلا قصّ.'],
                            ['title' => 'اللحظة الرسمية والجانبية', 'subtitle' => 'قصّ الشريط، والكلمة، واللقاءات.'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'timeline',
                'label' => 'كيف تسير الليلة',
                'hint' => 'مراحل التغطية بالترتيب',
                'title' => 'كيف تسير الليلة',
                'subtitle' => 'التسلسل نفسه في كل عرس، فتعرف كيف تسير التغطية قبل أن تبدأ الليلة.',
                'fields' => ['title' => 'العنوان', 'subtitle' => 'السطر تحته'],
                'item_label' => 'مرحلة',
                'item_fields' => [
                    'icon' => 'الأيقونة',
                    'title' => 'اسم المرحلة',
                    'subtitle' => 'وصفها في سطر',
                ],
                'items' => [
                    ['icon' => 'door', 'title' => 'الاستقبال', 'subtitle' => 'الوصول والتهاني'],
                    ['icon' => 'camera', 'title' => 'صور خاصة', 'subtitle' => 'ركن بإضاءة مضبوطة'],
                    ['icon' => 'crown', 'title' => 'الزفّة', 'subtitle' => 'لحظات تمرّ في ثوانٍ'],
                    ['icon' => 'users', 'title' => 'صور جماعية', 'subtitle' => 'مع العائلة والضيوف'],
                ],
            ],
            [
                'key' => 'weddings',
                'label' => 'معرض الزواجات',
                'hint' => 'مجموعات الصور من «الأعمال والمحتوى» — قسم المناسبات',
                'title' => 'من أعمال الزواجات',
                'subtitle' => 'مرتّبة بتسلسل الليلة — من الركن الهادئ إلى آخر لقطات الليل.',
                'fields' => ['title' => 'العنوان', 'subtitle' => 'السطر تحته'],
                'source' => Category::EVENTS,
                'item_label' => 'مجموعة',
                'items' => [
                    ['post' => 'burtreh-alaris'],
                    ['post' => 'fi-alqaa'],
                    ['post' => 'maqad-alaris'],
                    ['post' => 'albakhur-waldiyafa'],
                    ['post' => 'tafasil-laylat-alzawaj'],
                    ['post' => 'sayarat-alaris'],
                ],
            ],
            [
                'key' => 'corporate',
                'label' => 'معرض المناسبات والفعاليات',
                'hint' => 'مجموعات الصور من «الأعمال والمحتوى» — قسم الفعاليات',
                'title' => 'من أعمال المناسبات',
                'subtitle' => 'افتتاحات المشاريع والحملات المجتمعية وحفلات الشركات.',
                'fields' => ['title' => 'العنوان', 'subtitle' => 'السطر تحته'],
                'source' => Category::ACTIVITIES,
                'item_label' => 'مجموعة',
                'items' => [
                    ['post' => 'alfaaliyat-almuassasiya'],
                ],
            ],
            [
                'key' => 'faq',
                'label' => 'أسئلة قبل الحجز',
                'hint' => 'الأسئلة نفسها تُحرَّر من صفحة «الأسئلة الشائعة»',
                'title' => 'أسئلة قبل الحجز',
                'subtitle' => 'تُنشر كبيانات مهيكلة، فتقتبسها محرّكات البحث وأدوات الذكاء الاصطناعي كإجابة جاهزة.',
                'fields' => ['title' => 'العنوان', 'subtitle' => 'السطر تحته'],
            ],
            [
                'key' => 'cta',
                'label' => 'دعوة للحجز',
                'hint' => 'الشريط الأخير قبل التذييل',
                'title' => 'ليلتك تمرّ مرّة واحدة',
                'subtitle' => 'أرسل التاريخ والقاعة ونوع المناسبة، ونتفق على التفاصيل.',
                'fields' => ['title' => 'العنوان', 'subtitle' => 'السطر تحته'],
            ],
        ];
    }

    /**
     * التعريف المبدئي لمفتاح في صفحة، أو مصفوفة فارغة إن لم يكن معروفًا.
     *
     * @return array<string, mixed>
     */
    public static function definitionFor(string $page, string $key): array
    {
        foreach (static::definitions($page) as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return [];
    }

    /** خانة الصورة التي يملكها هذا القسم، إن كانت له خانة. */
    public function imageSlot(): ?string
    {
        $slot = self::definitionFor($this->page, $this->key)['image'] ?? null;

        return is_string($slot) ? $slot : null;
    }

    /** رابط القسم الفرعي الذي يأخذ منه القسم مجموعاته، إن كان معرضًا. */
    public function sourceCategory(): ?string
    {
        $source = self::definitionFor($this->page, $this->key)['source'] ?? null;

        return is_string($source) ? $source : null;
    }

    /** العنوان كما يظهر للزائر: المحرَّر إن وُجد، وإلا المبدئي. */
    public function heading(): ?string
    {
        return $this->resolve($this->title, 'title');
    }

    /** السطر المرافق للعنوان كما يظهر للزائر. */
    public function intro(): ?string
    {
        return $this->resolve($this->subtitle, 'subtitle');
    }

    /** الفقرة الطويلة كما تظهر للزائر. */
    public function text(): ?string
    {
        return $this->resolve($this->body, 'body');
    }

    /**
     * حقلٌ محرَّر يعلو على مبدئيّه، مع استبدال الرموز في كليهما.
     *
     * الفراغ يعني «أعد المبدئي» لا «احذف العنوان»: مالك يفرّغ حقلًا يريد
     * التراجع غالبًا لا صفحةً بقسم بلا عنوان. وإخفاء القسم كله له زرّه.
     */
    private function resolve(?string $edited, string $field): ?string
    {
        $value = trim((string) $edited) !== ''
            ? (string) $edited
            : (self::definitionFor($this->page, $this->key)[$field] ?? null);

        return is_string($value) ? self::tokens($value) : null;
    }

    /** يستبدل ‎:city‎ و‎:owner‎ بقيمتيهما من إعدادات الموقع. */
    public static function tokens(string $value): string
    {
        return strtr($value, [
            ':city' => (string) config('site.location.city'),
            ':owner' => (string) config('site.owner_name'),
        ]);
    }

    /**
     * أقسام الصفحة المعروضة بترتيبها، مع عناصرها.
     *
     * الرجوع إلى التعريفات حين يكون الجدول فارغًا مقصود: تثبيت جديد لم
     * تُشغَّل بذوره بعد يجب أن يعرض صفحة كاملة لا صفحة بيضاء — والنماذج
     * المبنيّة هنا غير محفوظة، فلا تُنشئ صفوفًا من تلقاء نفسها.
     *
     * @return EloquentCollection<int, self>
     */
    public static function visible(string $page): EloquentCollection
    {
        $blocks = self::query()
            ->onPage($page)
            ->where('is_active', true)
            ->ordered()
            ->with([
                'items' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
                'items.children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
                'items.post.media',
            ])
            ->get();

        if ($blocks->isNotEmpty() || self::query()->onPage($page)->exists()) {
            return $blocks;
        }

        return new EloquentCollection(array_map(
            fn (array $d): self => self::fromDefinition($page, $d),
            self::definitions($page),
        ));
    }

    /**
     * نموذج غير محفوظ مبنيّ على تعريف، بعناصره غير المحفوظة كذلك.
     *
     * @param  array<string, mixed>  $definition
     */
    public static function fromDefinition(string $page, array $definition): self
    {
        $block = new self([
            'page' => $page,
            'key' => $definition['key'],
            'label' => $definition['label'],
            'hint' => $definition['hint'] ?? null,
            'is_locked' => (bool) ($definition['locked'] ?? false),
        ]);

        $items = array_map(
            fn (array $item): PageBlockItem => PageBlockItem::fromDefinition($item),
            $definition['items'] ?? [],
        );

        $block->setRelation('items', new EloquentCollection($items));

        return $block;
    }
}
