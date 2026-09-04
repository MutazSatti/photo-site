<?php

namespace App\Console\Commands;

use App\Models\GoogleConnection;
use App\Services\GoogleReviewsService;
use Illuminate\Console\Command;
use Throwable;

class SyncGoogleReviews extends Command
{
    protected $signature = 'reviews:sync';

    protected $description = 'يستورد تقييمات Google Business Profile إلى آراء العملاء';

    public function handle(GoogleReviewsService $google): int
    {
        $connection = GoogleConnection::current();

        if (! $connection) {
            $this->components->warn('لا يوجد حساب Google مربوط. اربطه من لوحة التحكم ← تقييمات Google.');

            return self::SUCCESS;
        }

        if (! $connection->isReady()) {
            $this->components->warn('الحساب مربوط لكن بطاقة النشاط لم تُختر بعد.');

            return self::SUCCESS;
        }

        try {
            $stats = $google->sync($connection);
        } catch (Throwable $e) {
            // الخطأ يُحفظ ليظهر في لوحة التحكم بدل أن يضيع في السجلّات وحدها
            $connection->update(['last_error' => $e->getMessage()]);

            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'جديد: %d — محدَّث: %d — محذوف: %d — متجاوَز: %d',
            $stats['imported'],
            $stats['updated'],
            $stats['removed'],
            $stats['skipped'],
        ));

        return self::SUCCESS;
    }
}
