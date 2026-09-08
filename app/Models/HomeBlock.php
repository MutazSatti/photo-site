<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string|null $hint
 * @property string|null $title
 * @property string|null $subtitle
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_locked
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class HomeBlock extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
     * تعريف العناصر المتاحة وترتيبها المبدئي — المرجع الوحيد لهما.
     *
     * كل مفتاح يقابل ملفًا في resources/views/home/.
     *
     * العنوان والمقدّمة هنا هما ما كان مكتوبًا في القوالب قبل أن يصيرا
     * قابلين للتحرير، فيبقيان الافتراضَ الذي يعود إليه العنصر حين يُفرَّغ
     * حقله من اللوحة — لا فراغًا في مكان عنوان.
     *
     * ‎:city‎ و‎:owner‎ يُستبدلان عند العرض، فيتبع النصّ إعدادات الموقع ولا
     * يتجمّد على مدينة كُتبت مرة.
     *
     * @return array<int, array{key: string, label: string, hint: string, locked?: bool, title?: string, subtitle?: string}>
     */
    public static function definitions(): array
    {
        return [
            ['key' => 'hero', 'label' => 'الواجهة', 'hint' => 'الصورة الكبيرة والعنوان وأزرار الحجز', 'locked' => true],
            [
                'key' => 'sections', 'label' => 'الأقسام الرئيسية', 'hint' => 'بطاقات أقسام المعرض',
                'title' => 'أقسام المعرض',
                'subtitle' => 'كل قسم يحمل صوره وتفاصيله ولونه الخاص.',
            ],
            [
                'key' => 'services', 'label' => 'خدمات التصوير', 'hint' => 'الأقسام الفرعية لخدمات التصوير',
                'title' => 'خدمات التصوير في :city',
                'subtitle' => 'لكل تخصص متطلباته وأسلوبه في التغطية والتسليم.',
            ],
            [
                'key' => 'featured', 'label' => 'أعمال مختارة', 'hint' => 'الأعمال المعلّمة كمميّزة',
                'title' => 'أعمال مختارة',
                'subtitle' => 'نماذج من التغطيات الأخيرة — مناسبات ومؤتمرات وعقارات وبرامج تدريبية.',
            ],
            [
                'key' => 'workshops', 'label' => 'الورش التدريبية', 'hint' => 'أحدث الورش المنشورة',
                'title' => 'ورش تدريبية',
                'subtitle' => 'تعلّم التصوير بشكل عملي — مقاعد محدودة لضمان المتابعة الفردية.',
            ],
            [
                'key' => 'reading', 'label' => 'مقالات ومنشورات', 'hint' => 'أحدث المقالات والمنشورات التعليمية',
                'title' => 'اقرأ وتعلّم',
                'subtitle' => 'مقالات معمّقة ومنشورات تعليمية قصيرة — خلاصة تجربة ميدانية.',
            ],
            [
                'key' => 'clients', 'label' => 'جهات وثقت بعدستي', 'hint' => 'شعارات الجهات التي تعاملت معها',
                'title' => 'جهات وثقت بعدستي',
                'subtitle' => 'مؤسسات وشركات وجهات تدريبية غطّيتُ فعالياتها ومشاريعها في :city.',
            ],
            [
                'key' => 'testimonials', 'label' => 'آراء العملاء', 'hint' => 'المراجعات المنشورة',
                'title' => 'آراء العملاء',
            ],
            [
                'key' => 'faq', 'label' => 'الأسئلة الشائعة', 'hint' => 'تُنشر كبيانات مهيكلة تقرؤها أدوات الذكاء الاصطناعي',
                'title' => 'أسئلة شائعة',
                'subtitle' => 'أكثر ما يُسأل عنه قبل الحجز.',
            ],
            [
                'key' => 'cta', 'label' => 'دعوة للحجز', 'hint' => 'الشريط الأخير قبل التذييل',
                'title' => 'جاهز لتوثيق مناسبتك؟',
            ],
        ];
    }

    /** @var EloquentCollection<string, self>|null */
    private static ?EloquentCollection $memo = null;

    /**
     * عنصر بمفتاحه، أو نموذجًا غير محفوظ يرجع إلى التعريف المبدئي.
     *
     * تحتاجه المكوّنات التي تظهر خارج الصفحة الرئيسية أيضًا — كشريط دعوة
     * الحجز في أسفل كل صفحة — فتقرأ نصّه من المصدر نفسه بلا استعلام لكل
     * صفحة. والنموذج غير المحفوظ يعني أن قراءةً لا تُنشئ صفًّا أبدًا.
     */
    public static function for(string $key): self
    {
        self::$memo ??= self::query()->get()->keyBy('key');

        return self::$memo->get($key) ?? new self(['key' => $key]);
    }

    /** يُستدعى بعد كل تعديل على العناصر. */
    public static function forget(): void
    {
        self::$memo = null;
    }

    /**
     * التعريف المبدئي لمفتاح، أو مصفوفة فارغة إن لم يكن معروفًا.
     *
     * @return array<string, mixed>
     */
    private static function definitionFor(string $key): array
    {
        foreach (static::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return [];
    }

    /** عنوان العنصر كما يظهر للزائر: المحرَّر إن وُجد، وإلا المبدئي. */
    public function heading(): ?string
    {
        return $this->resolve($this->title, 'title');
    }

    /** مقدّمة العنصر كما تظهر للزائر: المحرَّرة إن وُجدت، وإلا المبدئية. */
    public function intro(): ?string
    {
        return $this->resolve($this->subtitle, 'subtitle');
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
            : (self::definitionFor($this->key)[$field] ?? null);

        if ($value === null) {
            return null;
        }

        return strtr($value, [
            ':city' => (string) config('site.location.city'),
            ':owner' => (string) config('site.owner_name'),
        ]);
    }

    /**
     * مفاتيح العناصر المعروضة بالترتيب.
     *
     * بلا تخزين مؤقت: استعلام واحد على جدول من تسعة صفوف كسبه مهمل، وثمنه سطح
     * إبطال يسهل أن يتسرّب منه خطأ — تحديث جماعي عبر باني الاستعلام لا يُطلق
     * أحداث النموذج فيبقى الكاش قديمًا بصمت. والصفحة تستدعيه مرة واحدة على أي
     * حال لأن الخاصية المحسوبة في مكوّن Livewire تحفظ نتيجته طوال التصيير.
     *
     * الرجوع إلى الترتيب المبدئي حين يكون الجدول فارغًا مقصود: تثبيت جديد لم
     * تُشغَّل بذوره بعد يجب أن يعرض صفحة كاملة لا صفحة بيضاء.
     *
     * @return array<int, string>
     */
    public static function visibleKeys(): array
    {
        return self::visible()->pluck('key')->all();
    }

    /**
     * العناصر المعروضة بالترتيب، نماذجَ لا مفاتيح.
     *
     * القالب يحتاج العنوان والمقدّمة مع المفتاح، فإرجاع النماذج يوفّر
     * استعلامًا ثانيًا لكل عنصر ويُبقي المصدر واحدًا.
     *
     * الرجوع إلى الترتيب المبدئي حين يكون الجدول فارغًا مقصود: تثبيت جديد لم
     * تُشغَّل بذوره بعد يجب أن يعرض صفحة كاملة لا صفحة بيضاء — والنماذج
     * المبنيّة هنا غير محفوظة، فلا تُنشئ صفوفًا من تلقاء نفسها.
     *
     * @return EloquentCollection<int, self>
     */
    public static function visible(): EloquentCollection
    {
        $blocks = self::query()->active()->ordered()->get();

        if ($blocks->isNotEmpty() || self::query()->exists()) {
            return $blocks;
        }

        // مجموعة Eloquent لا Support: النوعان لا يتبادلان في التحقّق الساكن،
        // وتوحيدهما هنا يجنّب المستدعي فرعين لشيء واحد
        return new EloquentCollection(array_map(fn (array $d): self => new self([
            'key' => $d['key'],
            'label' => $d['label'],
            'hint' => $d['hint'],
        ]), self::definitions()));
    }
}
