<?php

use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use App\Services\ImageService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin', ['title' => 'لوحة المعلومات'])] class extends Component
{
    #[Computed]
    public function stats(): array
    {
        return [
            'posts' => Post::count(),
            'published' => Post::published()->count(),
            'drafts' => Post::where('status', 'draft')->count(),
            'media' => Media::count(),
            'views' => (int) Post::sum('views'),
            'messages' => ContactMessage::count(),
            'unread' => ContactMessage::unread()->count(),
        ];
    }

    #[Computed]
    public function sectionCounts()
    {
        return Section::query()
            ->active()
            ->ordered()
            ->withCount([
                'posts',
                'posts as published_count' => fn ($q) => $q->published(),
            ])
            ->get();
    }

    #[Computed]
    public function recentMessages()
    {
        return ContactMessage::query()->latest()->take(5)->get();
    }

    #[Computed]
    public function recentPosts()
    {
        return Post::query()
            ->with(['section:id,name,slug', 'category:id,name,slug,section_id', 'media'])
            ->latest('updated_at')
            ->take(5)
            ->get();
    }

    /** عناصر منشورة بلا صور — تظهر فارغة للزائر ولا تُفهرس صورها. */
    #[Computed]
    public function postsMissingImages()
    {
        return Post::query()->published()->doesntHave('media')->count();
    }

    #[Computed]
    public function webpReady(): bool
    {
        return ImageService::webpSupported();
    }
}; ?>

<div>
    <x-admin.page-header
        title="لوحة المعلومات"
        :description="'مرحبًا ' . auth()->user()?->name . ' — هذه حالة الموقع الآن.'"
    >
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.posts.create') }}" icon="plus">إضافة عنصر</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @unless ($this->webpReady)
        <x-ui.alert variant="warning" title="تحويل الصور إلى WebP غير متاح" class="mb-6">
            إضافة GD أو Imagick غير مفعّلة في PHP الحالي، لذا سيفشل رفع الصور.
            فعّل إضافة GD في ملف php.ini ثم أعد تشغيل الخادم.
        </x-ui.alert>
    @endunless

    @if ($this->postsMissingImages > 0)
        <x-ui.alert variant="info" class="mb-6">
            لديك {{ $this->postsMissingImages }} عنصر منشور بلا صور. أضف صورًا لها لتظهر بشكل صحيح في المعرض وفي نتائج البحث.
        </x-ui.alert>
    @endif

    {{-- ================= أرقام سريعة ================= --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat
            label="عنصر منشور"
            :value="number_format($this->stats['published'])"
            icon="images"
            :hint="$this->stats['drafts'] ? $this->stats['drafts'] . ' مسوّدة' : null"
            :href="route('admin.posts')"
        />
        <x-admin.stat label="صورة" :value="number_format($this->stats['media'])" icon="image" hint="بصيغة WebP" />
        <x-admin.stat label="مشاهدة" :value="number_format($this->stats['views'])" icon="eye" />
        <x-admin.stat
            label="رسالة"
            :value="number_format($this->stats['messages'])"
            icon="inbox"
            :hint="$this->stats['unread'] ? $this->stats['unread'] . ' غير مقروءة' : 'لا جديد'"
            :href="route('admin.messages')"
        />
    </div>

    <div class="grid gap-6 mt-6 lg:grid-cols-2">

        {{-- ================= المحتوى حسب القسم ================= --}}
        <x-admin.card title="المحتوى حسب القسم" description="عدد العناصر المنشورة في كل قسم من أقسام المعرض.">
            <ul class="space-y-3">
                @foreach ($this->sectionCounts as $section)
                    <li>
                        <div class="flex items-center justify-between gap-3 mb-1.5">
                            <span class="flex items-center gap-2 text-sm font-bold text-ink-800 dark:text-ink-200">
                                <x-icon :name="$section->icon" :size="15" class="text-ink-400" />
                                {{ $section->name }}
                            </span>
                            <span class="text-sm text-ink-500 dark:text-ink-400" dir="ltr">
                                {{ $section->published_count }} / {{ $section->posts_count }}
                            </span>
                        </div>

                        @php
                            $max = max(1, $this->sectionCounts->max('posts_count'));
                            $width = round(($section->posts_count / $max) * 100);
                        @endphp

                        <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ $width }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-admin.card>

        {{-- ================= أحدث الرسائل ================= --}}
        <x-admin.card title="أحدث الرسائل" description="طلبات الحجز الواردة من صفحة التواصل.">
            <x-slot:actions>
                <x-ui.button href="{{ route('admin.messages') }}" variant="ghost" size="sm">عرض الكل</x-ui.button>
            </x-slot:actions>

            @if ($this->recentMessages->isNotEmpty())
                <ul class="divide-y divide-ink-200 dark:divide-ink-800">
                    @foreach ($this->recentMessages as $message)
                        <li class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <span @class([
                                'mt-1.5 size-2 shrink-0 rounded-full',
                                'bg-brand-500' => $message->status === 'new',
                                'bg-ink-300 dark:bg-ink-700' => $message->status !== 'new',
                            ])></span>

                            <div class="min-w-0 grow">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-bold truncate text-ink-900 dark:text-ink-100">{{ $message->name }}</p>
                                    <span class="text-xs text-ink-400 shrink-0" dir="ltr">{{ $message->phone }}</span>
                                </div>
                                <p class="mt-0.5 line-clamp-1 text-xs text-ink-500 dark:text-ink-400">{{ $message->message }}</p>
                            </div>

                            <span class="text-xs shrink-0 text-ink-400">{{ $message->created_at->diffForHumans(short: true) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="py-6 text-sm text-center text-ink-500 dark:text-ink-400">لا توجد رسائل بعد.</p>
            @endif
        </x-admin.card>
    </div>

    {{-- ================= آخر ما عُدّل ================= --}}
    <x-admin.card title="آخر ما عُدّل" class="mt-6" :padded="false">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.posts') }}" variant="ghost" size="sm">كل المحتوى</x-ui.button>
        </x-slot:actions>

        @if ($this->recentPosts->isNotEmpty())
            <ul class="divide-y divide-ink-200 dark:divide-ink-800">
                @foreach ($this->recentPosts as $post)
                    <li class="flex items-center gap-4 px-5 py-3">
                        <div class="overflow-hidden rounded-lg size-12 shrink-0 bg-ink-100 dark:bg-ink-800">
                            <x-site.picture :media="$post->coverImage()" variant="thumb" class="size-full" />
                        </div>

                        <div class="min-w-0 grow">
                            <p class="text-sm font-bold truncate text-ink-900 dark:text-ink-100">{{ $post->title }}</p>
                            <p class="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                {{ $post->section->name ?? '—' }}
                                @if ($post->category) · {{ $post->category->name }} @endif
                                · عُدّل {{ $post->updated_at->diffForHumans() }}
                            </p>
                        </div>

                        <x-ui.badge :variant="$post->status === 'published' ? 'success' : 'neutral'" class="shrink-0">
                            {{ $post->status === 'published' ? 'منشور' : 'مسوّدة' }}
                        </x-ui.badge>

                        <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                            class="shrink-0 rounded-lg p-2 text-ink-500 transition-colors hover:bg-ink-100 dark:hover:bg-ink-800"
                            aria-label="تعديل {{ $post->title }}">
                            <x-icon name="pencil" :size="16" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="px-5 py-10 text-sm text-center text-ink-500 dark:text-ink-400">لم يُضف محتوى بعد.</p>
        @endif
    </x-admin.card>
</div>
