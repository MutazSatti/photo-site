<?php

namespace Database\Seeders;

use App\Models\PageBlock;
use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * يبذر أقسام الصفحات المصمَّمة من PageBlock::definitions().
 *
 * ما يُكتب هنا في القاعدة هو ما يبدأ منه المالك في اللوحة. والنصّ نفسه يبقى في
 * التعريفات مرجعًا يعود إليه الحقل حين يُفرَّغ، فالبذرة لا تقطع الصلة به.
 *
 * والبذرة لا تعيد ما حذفه المالك: الأقسام تُحدَّث في مسمّياتها الإدارية فقط،
 * والعناصر لا تُبذر إلا في قسم لا عنصر فيه — فمن حذف رقمًا من الشريط لا يجده
 * قد عاد بعد أوّل نشر.
 */
class PageBlockSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys(PageBlock::pages()) as $page) {
            $this->seedPage($page);
        }
    }

    public function seedPage(string $page): void
    {
        foreach (PageBlock::definitions($page) as $index => $definition) {
            $exists = PageBlock::query()
                ->onPage($page)
                ->where('key', $definition['key'])
                ->exists();

            $block = PageBlock::updateOrCreate(
                ['page' => $page, 'key' => $definition['key']],
                [
                    'label' => $definition['label'],
                    'hint' => $definition['hint'] ?? null,
                    'is_locked' => (bool) ($definition['locked'] ?? false),
                    // الترتيب والظهور قرار المالك — لا يُعاد ضبطهما على صف موجود
                    ...($exists ? [] : [
                        'title' => $definition['title'] ?? null,
                        'subtitle' => $definition['subtitle'] ?? null,
                        'body' => $definition['body'] ?? null,
                        'sort_order' => $index,
                        'is_active' => true,
                    ]),
                ],
            );

            if ($block->allItems()->exists()) {
                continue;
            }

            $this->seedItems($block, $definition['items'] ?? [], null);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function seedItems(PageBlock $block, array $items, ?int $parentId): void
    {
        foreach ($items as $index => $item) {
            $post = isset($item['post']) ? Post::where('slug', $item['post'])->first() : null;

            // مجموعة لم تُنشأ بعد لا تُبذر عنصرًا يشير إلى فراغ
            if (isset($item['post']) && ! $post) {
                continue;
            }

            $created = $block->allItems()->create([
                'parent_id' => $parentId,
                'post_id' => $post?->id,
                'icon' => $item['icon'] ?? null,
                'title' => $item['title'] ?? null,
                'subtitle' => $item['subtitle'] ?? null,
                'body' => $item['body'] ?? null,
                'sort_order' => $index,
                'is_active' => true,
            ]);

            $this->seedItems($block, $item['children'] ?? [], $created->id);
        }
    }
}
