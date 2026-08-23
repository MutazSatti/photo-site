<?php

use App\Http\Controllers\FeedController;
use App\Http\Controllers\SyncController;
use App\Models\Section;
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
|   /services/events/{slug}              عمل مصوّر
|   /articles/{slug}                     مقال (قسم بلا أقسام فرعية)
|
| القيود على معامل section هي ما يمنع التباس المسارين ذوي المقطعين:
| قسم "خدمات التصوير" وحده يملك أقسامًا فرعية، وبقية الأقسام تحمل عناصر مباشرة.
|
*/

$sectionsWithCategories = Section::SERVICES;
$sectionsWithoutCategories = implode('|', [Section::WORKSHOPS, Section::ARTICLES, Section::TIPS]);
$allSections = $sectionsWithCategories.'|'.$sectionsWithoutCategories;

Route::livewire('/{section}', 'pages::section')
    ->where('section', $allSections)
    ->name('section.show');

Route::livewire('/{section}/{category}', 'pages::category')
    ->where('section', $sectionsWithCategories)
    ->name('category.show');

Route::livewire('/{section}/{post}', 'pages::post')
    ->where('section', $sectionsWithoutCategories)
    ->name('post.show');

Route::livewire('/{section}/{category}/{post}', 'pages::post')
    ->where('section', $sectionsWithCategories)
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
    Route::livewire('/messages', 'pages::admin.messages')->name('messages');
    Route::livewire('/settings', 'pages::admin.settings')->name('settings');
});

// المسار الافتراضي بعد تسجيل الدخول
Route::redirect('/dashboard', '/admin/dashboard')->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
