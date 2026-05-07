<x-layout>
    <x-slot:title>My Posts</x-slot>

    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">My Posts</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $published->count() }} published &middot;
                {{ $drafts->count() }} draft(s) &middot;
                {{ $scheduled->count() }} scheduled
            </p>
        </div>
        <a href="{{ route('posts.create') }}"
           class="rounded-md bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-700">
            New Post
        </a>
    </div>

    @php
        $sections = [
            ['title' => 'Drafts',    'badge' => 'bg-yellow-100 text-yellow-800', 'posts' => $drafts,    'meta' => fn ($p) => 'updated ' . $p->updated_at->diffForHumans()],
            ['title' => 'Scheduled', 'badge' => 'bg-blue-100 text-blue-800',     'posts' => $scheduled, 'meta' => fn ($p) => 'goes live ' . $p->published_at->format('Y-m-d H:i')],
            ['title' => 'Published', 'badge' => 'bg-green-100 text-green-800',   'posts' => $published, 'meta' => fn ($p) => 'on ' . $p->published_at->format('Y-m-d H:i')],
        ];
    @endphp

    @foreach ($sections as $section)
        <section class="mb-8">
            <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-700">
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $section['badge'] }}">
                    {{ $section['title'] }}
                </span>
                <span class="text-gray-400">({{ $section['posts']->count() }})</span>
            </h2>

            @forelse ($section['posts'] as $post)
                <article class="mb-2 flex items-center justify-between rounded-md border border-gray-200 bg-white px-4 py-3 shadow-sm">
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-gray-900">
                            {{ $post->title }}
                        </div>
                        <div class="mt-0.5 text-xs text-gray-500">
                            {{ $section['meta']($post) }}
                        </div>
                    </div>

                    <div class="ml-4 flex items-center gap-3 text-xs">
                        <a href="{{ route('posts.show', $post) }}" class="text-gray-600 hover:text-gray-900">View</a>
                        <a href="{{ route('posts.edit', $post) }}" class="text-gray-600 hover:text-gray-900">Edit</a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}"
                              onsubmit="return confirm('Delete this post?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="px-4 py-2 text-sm text-gray-400">No posts in this state.</p>
            @endforelse
        </section>
    @endforeach
</x-layout>
