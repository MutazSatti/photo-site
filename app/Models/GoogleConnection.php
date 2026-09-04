<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $refresh_token
 * @property string|null $connected_email
 * @property string|null $account_name
 * @property string|null $location_name
 * @property string|null $location_title
 * @property int $min_rating
 * @property bool $auto_publish
 * @property CarbonInterface|null $last_synced_at
 * @property int $last_imported
 * @property string|null $last_error
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class GoogleConnection extends Model
{
    protected $guarded = [];

    /**
     * تكرار لقيم قاعدة البيانات الافتراضية.
     *
     * بدونها يبقى الكائن المُنشأ حديثًا بقيم null حتى يُقرأ من القاعدة من جديد،
     * فيتحوّل min_rating إلى صفر و auto_publish إلى false — ويُستورد كل شيء مخفيًا
     * في أول مزامنة بعد الربط دون سبب ظاهر.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'min_rating' => 4,
        'auto_publish' => true,
        'last_imported' => 0,
    ];

    protected function casts(): array
    {
        return [
            // التشفير على مستوى العمود: من يقرأ نسخة من قاعدة البيانات لا يحصل
            // على رمز يفتح حساب صاحب الموقع
            'refresh_token' => 'encrypted',
            'auto_publish' => 'boolean',
            'min_rating' => 'integer',
            'last_imported' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    /** الاتصال الوحيد — الموقع لمصور واحد وبطاقة نشاط واحدة. */
    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    /** جاهز للمزامنة فقط عندما يكون الموقع الجغرافي قد اختير. */
    public function isReady(): bool
    {
        return filled($this->account_name) && filled($this->location_name);
    }

    /**
     * المعرّف المجرّد للموقع بلا بادئة المسار.
     *
     * Google ترجعه بصيغة "locations/123"، بينما تتوقّعه واجهة التقييمات رقمًا
     * مجرّدًا داخل مسار تبنيه بنفسك.
     */
    public function locationId(): ?string
    {
        return $this->location_name
            ? str($this->location_name)->afterLast('/')->value()
            : null;
    }
}
