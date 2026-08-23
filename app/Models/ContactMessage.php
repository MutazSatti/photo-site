<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $service
 * @property CarbonInterface|null $event_date
 * @property string $message
 * @property string $status
 * @property string|null $ip
 * @property string|null $user_agent
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class ContactMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'read' => 'مقروءة',
            'replied' => 'تم الرد',
            'archived' => 'مؤرشفة',
            default => 'جديدة',
        };
    }
}
