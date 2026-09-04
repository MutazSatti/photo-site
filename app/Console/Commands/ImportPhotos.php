<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

/**
 * استيراد مجلد صور أصلية كعمل واحد في المعرض.
 *
 * المنطق الافتراضي: المجلد = مشروع واحد، وصوره معرضه. هذا يطابق طريقة عمل
 * المصوّر فعلًا — مجلد لكل عقار أو مناسبة — ويجنّبه إنشاء العناصر واحدًا واحدًا
 * من لوحة التحكم.
 */
class ImportPhotos extends Command
{
    protected $signature = 'photos:import
        {folder : مسار المجلد الذي يحوي الصور الأصلية}
        {--title= : عنوان العمل (الافتراضي: اسم المجلد)}
        {--section=services : رابط القسم الرئيسي}
        {--category= : رابط القسم الفرعي، مثل real-estate}
        {--location= : مكان التصوير}
        {--into= : إضافة الصور إلى عمل قائم بمعرّفه النصّي بدل إنشاء عمل جديد}
        {--each : عمل مستقل لكل صورة بدل عمل واحد يجمعها}
        {--publish : ينشر مباشرة بدل الحفظ كمسودة}
        {--dry : يعرض ما سيحدث دون تنفيذ}';

    protected $description = 'يستورد مجلد صور أصلية إلى المعرض ويحوّلها إلى WebP';

    public function handle(ImageService $images): int
    {
        $folder = rtrim($this->argument('folder'), '\\/');

        if (! is_dir($folder)) {
            $this->components->error("المجلد غير موجود: {$folder}");

            return self::FAILURE;
        }

        if (! ImageService::webpSupported()) {
            $this->components->error('إضافة GD أو Imagick غير مفعّلة، فلا يمكن التحويل إلى WebP.');

            return self::FAILURE;
        }

        $files = $this->imagesIn($folder);

        if ($files === []) {
            $this->components->warn('لا صور في هذا المجلد.');

            return self::SUCCESS;
        }

        $section = Section::where('slug', $this->option('section'))->first();

        if (! $section) {
            $this->components->error('القسم الرئيسي غير موجود: '.$this->option('section'));

            return self::FAILURE;
        }

        $category = null;

        if ($slug = $this->option('category')) {
            $category = Category::where('section_id', $section->id)->where('slug', $slug)->first();

            if (! $category) {
                $this->components->error("القسم الفرعي «{$slug}» غير موجود تحت «{$section->name}».");

                return self::FAILURE;
            }
        }

        $title = $this->option('title') ?: basename($folder);

        $this->components->info(sprintf(
            '%d صورة → %s%s',
            count($files),
            $section->name,
            $category ? " ← {$category->name}" : '',
        ));

        /*
         * الإضافة إلى عمل قائم: صفحات الخدمات تعرض مجموعات ثابتة، وصور المجموعة
         * الواحدة تأتي من مجلدات مشاريع متفرّقة. فبدل عمل جديد لكل مجلد، تُضاف
         * دفعةً بعد أخرى إلى المجموعة نفسها.
         *
         * يُتحقّق منها قبل العرض التجريبي، فيكشف ‎--dry خطأ المعرّف قبل التنفيذ.
         */
        $existing = null;

        if ($slug = $this->option('into')) {
            if ($this->option('each')) {
                $this->components->error('‎--into و‎--each لا يجتمعان: الأول يضيف إلى عمل واحد والثاني يفرّق الصور على أعمال.');

                return self::FAILURE;
            }

            $existing = Post::where('slug', $slug)->first();

            if (! $existing) {
                $this->components->error("لا يوجد عمل بالمعرّف «{$slug}».");

                return self::FAILURE;
            }
        }

        if ($this->option('dry')) {
            foreach ($files as $file) {
                $this->line('  '.basename($file).'  ('.$this->humanSize($file).')');
            }
            $this->components->warn('عرض فقط — لم يُحفظ شيء. أزل ‎--dry للتنفيذ.');

            return self::SUCCESS;
        }

        $groups = $this->option('each')
            ? array_map(fn (string $f) => [$f], $files)
            : [$files];

        $created = 0;

        foreach ($groups as $index => $group) {
            $groupTitle = count($groups) > 1
                ? $title.' — '.($index + 1)
                : $title;

            $post = $existing ?? $this->createPost($groupTitle, $section, $category);
            $bar = $this->output->createProgressBar(count($group));
            $bar->start();

            foreach ($group as $position => $path) {
                try {
                    $images->store(
                        file: new UploadedFile($path, basename($path), null, null, true),
                        post: $post,
                        // الإضافة إلى عمل قائم لا تسحب غلافه من صورته الحالية
                        isCover: $position === 0 && ! $post->media()->where('is_cover', true)->exists(),
                    );
                } catch (Throwable $e) {
                    $this->newLine();
                    $this->components->warn(basename($path).' — تعذّر: '.$e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->components->twoColumnDetail(
                $post->title,
                $post->media()->count().' صورة · '.($post->status === 'published' ? 'منشور' : 'مسودة'),
            );

            $created++;
        }

        cache()->forget('sync.payload');
        cache()->forget('sync.manifest');

        $this->newLine();

        $this->components->info($existing
            ? "أُضيفت الصور إلى «{$existing->title}»."
            : "تم إنشاء {$created} عمل.");

        if (! $existing && ! $this->option('publish')) {
            $this->components->warn('حُفظت كمسودات — راجع النصوص والنص البديل من لوحة التحكم ثم انشرها.');
        }

        return self::SUCCESS;
    }

    private function createPost(string $title, Section $section, ?Category $category): Post
    {
        $slug = Str::slug($title) ?: 'work-'.Str::lower(Str::random(6));
        $base = $slug;
        $n = 2;

        while (Post::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return Post::create([
            'section_id' => $section->id,
            'category_id' => $category?->id,
            'slug' => $slug,
            'title' => $title,
            'location' => $this->option('location'),
            'status' => $this->option('publish') ? 'published' : 'draft',
            'published_at' => $this->option('publish') ? now() : null,
            'sort_order' => (int) Post::max('sort_order') + 1,
        ]);
    }

    /**
     * صور المجلد مرتّبة باسمها.
     *
     * الترتيب مقصود: أسماء ملفات الكاميرا متسلسلة، فترتيبها يحفظ تسلسل التصوير
     * ويجعل أول صورة — وهي غالبًا الواجهة أو اللقطة العامة — غلافًا للعمل.
     *
     * @return array<int, string>
     */
    private function imagesIn(string $folder): array
    {
        $files = glob($folder.'/*.{jpg,jpeg,JPG,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE) ?: [];

        $files = array_values(array_filter($files, 'is_file'));
        natcasesort($files);

        return array_values($files);
    }

    private function humanSize(string $path): string
    {
        return round(filesize($path) / 1048576, 1).' ميجا';
    }
}
