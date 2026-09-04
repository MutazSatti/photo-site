<?php

namespace App\Http\Controllers;

use App\Services\GoogleReviewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * موافقة Google تتم على شاشة Google نفسها؛ الموقع لا يرى كلمة المرور إطلاقًا،
 * وإنما يستلم رمزًا يبادله برمز تحديث.
 */
class GoogleOAuthController extends Controller
{
    public function redirect(Request $request, GoogleReviewsService $google): RedirectResponse
    {
        if (! GoogleReviewsService::configured()) {
            return to_route('admin.google')
                ->with('google_error', 'أضف GOOGLE_CLIENT_ID و GOOGLE_CLIENT_SECRET في ملف .env أولًا.');
        }

        // state يربط الرد بالجلسة نفسها ويمنع تزوير طلب الربط
        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($google->authUrl($state));
    }

    public function callback(Request $request, GoogleReviewsService $google): RedirectResponse
    {
        $expected = $request->session()->pull('google_oauth_state');

        if (blank($expected) || ! hash_equals($expected, (string) $request->query('state'))) {
            return to_route('admin.google')
                ->with('google_error', 'انتهت صلاحية طلب الربط أو لم يطابق الجلسة. أعد المحاولة.');
        }

        if ($request->filled('error')) {
            return to_route('admin.google')
                ->with('google_error', 'أُلغيت الموافقة على Google.');
        }

        if (! $request->filled('code')) {
            return to_route('admin.google')
                ->with('google_error', 'لم يصل رمز الموافقة من Google.');
        }

        try {
            $google->connect($request->string('code')->value());
        } catch (Throwable $e) {
            return to_route('admin.google')->with('google_error', $e->getMessage());
        }

        return to_route('admin.google')->with('google_status', 'تم ربط حساب Google. اختر بطاقة نشاطك الآن.');
    }
}
