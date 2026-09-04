<?php

namespace App\Services;

use App\Models\GoogleConnection;
use App\Models\Testimonial;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * استيراد تقييمات Google Business Profile إلى جدول آراء العملاء.
 *
 * التقييمات المستوردة تُحفظ بـ source = google، ومصدرها هذا يستثنيها من
 * AggregateRating في البيانات المهيكلة — انظر App\Support\Schema::aggregateRating().
 */
class GoogleReviewsService
{
    private const OAUTH_AUTH = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const OAUTH_TOKEN = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/business.manage';

    /** واجهات Business Profile موزّعة على ثلاثة نطاقات مختلفة. */
    private const API_ACCOUNTS = 'https://mybusinessaccountmanagement.googleapis.com/v1';

    private const API_INFO = 'https://mybusinessbusinessinformation.googleapis.com/v1';

    private const API_REVIEWS = 'https://mybusiness.googleapis.com/v4';

    public static function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    /**
     * رابط شاشة موافقة Google.
     *
     * access_type=offline مع prompt=consent هما ما يضمنان وصول refresh_token —
     * بدونهما تعيد Google رمز وصول قصير العمر فقط، فينقطع الاستيراد بعد ساعة.
     */
    public function authUrl(string $state): string
    {
        return self::OAUTH_AUTH.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('admin.google.callback'),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /** يبادل رمز الموافقة برمز تحديث دائم ويحفظ الاتصال. */
    public function connect(string $code): GoogleConnection
    {
        $response = Http::asForm()->post(self::OAUTH_TOKEN, [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('admin.google.callback'),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed() || blank($response->json('refresh_token'))) {
            throw new RuntimeException(
                'لم تُرجع Google رمز تحديث. تأكّد من إزالة صلاحية التطبيق السابقة من حسابك ثم أعد المحاولة.'
            );
        }

        $connection = GoogleConnection::current() ?? new GoogleConnection;

        $connection->fill([
            'refresh_token' => $response->json('refresh_token'),
            'connected_email' => $this->emailFromIdToken($response->json('id_token')),
            'last_error' => null,
        ])->save();

        Cache::forget($this->accessTokenCacheKey());

        return $connection->refresh();
    }

    /** رمز وصول صالح، مخزّن مؤقتًا حتى قُبيل انتهائه. */
    public function accessToken(GoogleConnection $connection): string
    {
        return Cache::remember($this->accessTokenCacheKey(), now()->addMinutes(50), function () use ($connection) {
            $response = Http::asForm()->post(self::OAUTH_TOKEN, [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    'تعذّر تجديد صلاحية الوصول إلى Google. قد تكون الصلاحية أُلغيت — أعد الربط.'
                );
            }

            return (string) $response->json('access_token');
        });
    }

    private function accessTokenCacheKey(): string
    {
        return 'google.access_token';
    }

    private function client(GoogleConnection $connection): PendingRequest
    {
        return Http::withToken($this->accessToken($connection))
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 500, throw: false);
    }

    /**
     * حسابات Business Profile المتاحة للمستخدم.
     *
     * @return array<int, array{name: string, label: string}>
     */
    public function accounts(GoogleConnection $connection): array
    {
        $response = $this->client($connection)->get(self::API_ACCOUNTS.'/accounts');

        $this->guard($response, 'تعذّر جلب حسابات Google.');

        $accounts = [];

        foreach ((array) $response->json('accounts', []) as $account) {
            $name = (string) ($account['name'] ?? '');

            if ($name !== '') {
                $accounts[] = ['name' => $name, 'label' => (string) ($account['accountName'] ?? $name)];
            }
        }

        return $accounts;
    }

    /**
     * المواقع (البطاقات) تحت حساب معيّن.
     *
     * @return array<int, array{name: string, label: string}>
     */
    public function locations(GoogleConnection $connection, string $accountName): array
    {
        $response = $this->client($connection)->get(self::API_INFO.'/'.$accountName.'/locations', [
            'readMask' => 'name,title,storefrontAddress',
            'pageSize' => 100,
        ]);

        $this->guard($response, 'تعذّر جلب مواقع النشاط.');

        $locations = [];

        foreach ((array) $response->json('locations', []) as $location) {
            $name = (string) ($location['name'] ?? '');

            if ($name !== '') {
                $locations[] = ['name' => $name, 'label' => (string) ($location['title'] ?? $name)];
            }
        }

        return $locations;
    }

    /**
     * كل التقييمات، عبر كل الصفحات.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchReviews(GoogleConnection $connection): array
    {
        // واجهة التقييمات ما زالت على v4 وتتطلّب مسار الحساب والموقع معًا
        $endpoint = self::API_REVIEWS.'/'.$connection->account_name
            .'/locations/'.$connection->locationId().'/reviews';

        $reviews = [];
        $pageToken = null;

        do {
            $response = $this->client($connection)->get($endpoint, array_filter([
                'pageSize' => 50,
                'pageToken' => $pageToken,
            ]));

            $this->guard($response, 'تعذّر جلب التقييمات من Google.');

            $reviews = array_merge($reviews, $response->json('reviews', []));
            $pageToken = $response->json('nextPageToken');

            // حاجز أمان: بطاقة واحدة لن تتجاوز هذا الحد عمليًا
        } while ($pageToken && count($reviews) < 2000);

        return $reviews;
    }

    /**
     * يزامن التقييمات إلى جدول الآراء.
     *
     * @return array{imported: int, updated: int, removed: int, skipped: int}
     */
    public function sync(GoogleConnection $connection): array
    {
        if (! $connection->isReady()) {
            throw new RuntimeException('اختر بطاقة النشاط أولًا قبل المزامنة.');
        }

        $reviews = $this->fetchReviews($connection);

        $stats = ['imported' => 0, 'updated' => 0, 'removed' => 0, 'skipped' => 0];
        $seen = [];

        DB::transaction(function () use ($reviews, $connection, &$stats, &$seen) {
            foreach ($reviews as $review) {
                $externalId = $review['reviewId'] ?? ($review['name'] ?? null);
                $comment = trim((string) ($review['comment'] ?? ''));
                $rating = $this->starsToInt($review['starRating'] ?? null);

                if (! $externalId || $rating === 0) {
                    $stats['skipped']++;

                    continue;
                }

                $seen[] = $externalId;

                // تقييم بنجوم بلا نص لا يصلح للعرض كرأي مكتوب
                if ($comment === '') {
                    $stats['skipped']++;

                    continue;
                }

                $existing = Testimonial::where('external_id', $externalId)->first();

                $payload = [
                    'name' => trim((string) ($review['reviewer']['displayName'] ?? 'زائر على Google')),
                    'content' => $comment,
                    'rating' => $rating,
                    'source' => Testimonial::SOURCE_GOOGLE,
                    'external_id' => $externalId,
                    'reviewed_at' => $this->parseTime($review['updateTime'] ?? $review['createTime'] ?? null),
                ];

                if ($existing) {
                    // is_active لا يُلمس: قرار الإظهار للمالك، ولا يُعاد ضبطه مع كل مزامنة
                    $existing->update($payload);
                    $stats['updated']++;

                    continue;
                }

                Testimonial::create([
                    ...$payload,
                    'is_active' => $connection->auto_publish && $rating >= $connection->min_rating,
                    'sort_order' => (int) Testimonial::max('sort_order') + 1,
                ]);

                $stats['imported']++;
            }

            // تقييم حذفه صاحبه من Google يجب ألا يبقى معروضًا في الموقع.
            // الحذف مشروط بوصول قائمة غير فارغة، حتى لا يمسح ردٌّ ناقص كل شيء.
            if ($seen !== []) {
                $stats['removed'] = Testimonial::where('source', Testimonial::SOURCE_GOOGLE)
                    ->whereNotNull('external_id')
                    ->whereNotIn('external_id', $seen)
                    ->delete();
            }
        });

        $connection->update([
            'last_synced_at' => now(),
            'last_imported' => $stats['imported'],
            'last_error' => null,
        ]);

        Cache::forget('sync.payload');
        Cache::forget('sync.manifest');

        Log::info('تمت مزامنة تقييمات Google', $stats);

        return $stats;
    }

    /** يحوّل تعداد النجوم النصّي الذي ترجعه Google إلى رقم. */
    private function starsToInt(?string $star): int
    {
        return match ($star) {
            'ONE' => 1,
            'TWO' => 2,
            'THREE' => 3,
            'FOUR' => 4,
            'FIVE' => 5,
            default => 0,
        };
    }

    private function parseTime(?string $time): ?Carbon
    {
        if (blank($time)) {
            return null;
        }

        try {
            return Carbon::parse($time);
        } catch (\Throwable) {
            return null;
        }
    }

    /** البريد المرتبط بالموافقة — يُقرأ من الجزء الأوسط من id_token بلا تحقق تشفيري. */
    private function emailFromIdToken(?string $idToken): ?string
    {
        if (blank($idToken)) {
            return null;
        }

        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')) ?: '[]', true);

        return is_array($payload) ? ($payload['email'] ?? null) : null;
    }

    private function guard(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }

        $detail = $response->json('error.message') ?? $response->body();

        throw new RuntimeException($message.' ('.$response->status().') '.mb_substr((string) $detail, 0, 200));
    }
}
