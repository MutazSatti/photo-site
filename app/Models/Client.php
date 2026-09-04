<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string|null $name_en
 * @property string|null $url
 * @property int|null $media_id
 * @property int $sort_order
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Media|null $logo
 */
class Client extends Model
{
    /** يميّز شعارات الجهات عن بقية الصور غير المرتبطة بعنصر. */
    public const LOGO_USAGE = 'client_logo';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Media, $this> */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
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

    /** النص البديل للشعار — يُكتب مرة واحدة هنا ليتطابق عند الرفع وعند العرض. */
    public function logoAlt(): string
    {
        return 'شعار '.$this->name;
    }

    /**
     * الشعار ملك للجهة وحدها، فحذفها يحذف سجله — وخطّاف Media يتكفّل بمسح
     * ملفات المقاسات من القرص حتى لا تتراكم صور معطّلة.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $client) {
            $client->logo?->delete();
        });
    }
}
