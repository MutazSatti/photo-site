<?php

namespace Database\Seeders;

use App\Models\HomeBlock;
use Illuminate\Database\Seeder;

/**
 * التعريفات مصدرها HomeBlock::definitions() — المرجع نفسه الذي ترجع إليه
 * الصفحة حين يكون الجدول فارغًا، فلا يفترق الترتيبان.
 */
class HomeBlockSeeder extends Seeder
{
    public function run(): void
    {
        foreach (HomeBlock::definitions() as $index => $block) {
            $exists = HomeBlock::where('key', $block['key'])->exists();

            HomeBlock::updateOrCreate(
                ['key' => $block['key']],
                [
                    'label' => $block['label'],
                    'hint' => $block['hint'],
                    'is_locked' => $block['locked'] ?? false,
                    // الترتيب والظهور قرار المالك — لا يُعاد ضبطهما على صف موجود
                    ...($exists ? [] : ['sort_order' => $index, 'is_active' => true]),
                ],
            );
        }
    }
}
