<?php

use App\Http\Controllers\FeedController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\SyncController;
use App\Support\SectionRoutes;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| الواجهة العامة
|--------------------------------------------------------------------------
*/

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/portfolio', 'pages::portfolio')->name('portfolio');
Route::livewire('/about', 'pages::about')->name('about');
Route::livewire('/contact', 'pages::contact')->name('contact');
Route::livewire('/faq', 'pages::faq')->name('faq');

/*
|--------------------------------------------------------------------------
| المزامنة مع قاعدة بيانات المتصفح
|--------------------------------------------------------------------------
*/

Route::get('/sync/manifest', [SyncController::class, 'manifest'])->name('sync.manifest');
Route::get('/sync/data', [SyncController::class, 'data'])->name('sync.data');

/*
|--------------------------------------------------------------------------
| ملفات الفهرسة ومصادر البيانات للزواحف
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [FeedController::class, 'sitemap'])->name('sitemap');
Route::get('/feed.xml', [FeedController::class, 'rss'])->name('feed');
Route::get('/llms.txt', [FeedController::class, 'llms'])->name('llms');
Route::get('/robots.txt', [FeedController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| الأقسام والمحتوى
|--------------------------------------------------------------------------
|
| بنية الروابط هرمية ومقروءة:
|   /services                            قسم رئيسي
|   /services/events                     قسم فرعي
|   /services/events/{slug}              عنصر داخل قسم فرعي
|   /articles/{slug}                     عنصر مباشر تحت قسمه الرئيسي
|
| القيود مبنيّة من قاعدة البيانات لا من قائمة ثابتة، فأي قسم يُضاف من لوحة
| التحكم يعمل رابطه فورًا، وأي قسم يملك أقسامًا فرعية أو عناصر مباشرة أو
| الاثنين معًا يعمل كما هو متوقّع.
|
| المساران ذوا المقطعين يتمايزان بأن أسماء الأقسام الفرعية معروفة مسبقًا:
| ما يطابقها فهو قسم فرعي، وما عداه فهو عنصر. لذا يُسجَّل مسار القسم الفرعي
| أولًا.
|
*/

$patterns = SectionRoutes::patterns();

/*
 * صفحة خدمة بتصميم خاص تسبق النمط العام.
 *
 * التصوير العقاري يصله ثلاثة عملاء بأسئلة مختلفة، فصفحته صفحة مبيعات لا قائمة
 * أعمال. الرابط هو نفسه الذي يولّده Category::url()، والأسبقية في المطابقة
 * لترتيب التسجيل لا للاسم.
 */
Route::livewire('/services/real-estate', 'pages::services.real-estate')
    ->name('services.real-estate');

Route::livewire('/{section}', 'pages::section')
    ->where('section', $patterns['sections'])
    ->name('section.show');

Route::livewire('/{section}/{category}', 'pages::category')
    ->where('section', $patterns['sections'])
    ->where('category', $patterns['categories'])
    ->name('category.show');

Route::livewire('/{section}/{post}', 'pages::post')
    ->where('section', $patterns['sections'])
    ->name('post.show');

Route::livewire('/{section}/{category}/{post}', 'pages::post')
    ->where('section', $patterns['sections'])
    ->where('category', $patterns['categories'])
    ->name('work.show');

/*
|--------------------------------------------------------------------------
| لوحة التحكم
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard');

    Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/posts', 'pages::admin.posts')->name('posts');
    Route::livewire('/posts/create', 'pages::admin.post-edit')->name('posts.create');
    Route::livewire('/posts/{post}/edit', 'pages::admin.post-edit')->name('posts.edit');
    Route::livewire('/sections', 'pages::admin.sections')->name('sections');
    Route::livewire('/faqs', 'pages::admin.faqs')->name('faqs');
    Route::livewire('/testimonials', 'pages::admin.testimonials')->name('testimonials');
    Route::livewire('/clients', 'pages::admin.clients')->name('clients');
    Route::livewire('/messages', 'pages::admin.messages')->name('messages');
    Route::livewire('/google', 'pages::admin.google')->name('google');

    // موافقة Google تتم على شاشة Google؛ هذان المساران يبدآنها ويستقبلان ردّها
    Route::get('/google/connect', [GoogleOAuthController::class, 'redirect'])->name('google.connect');
    Route::get('/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');

    Route::livewire('/settings', 'pages::admin.settings')->name('settings');
});

// المسار الافتراضي بعد تسجيل الدخول
Route::redirect('/dashboard', '/admin/dashboard')->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
