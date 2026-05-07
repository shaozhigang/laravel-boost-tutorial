<x-layout>
    <x-slot:title>Posts</x-slot>

    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Posts</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $posts->total() }} published post(s).</p>
        </div>
    </div>

    @forelse ($posts as $post)
        <article class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">
                <a href="{{ route('posts.show', $post) }}" class="hover:underline">
                    {{ $post->title }}
                </a>
            </h2>

            <div class="mt-1 text-xs text-gray-500">
                <span>by {{ $post->author?->name ?? 'Unknown' }}</span>
                <span class="mx-1">&middot;</span>
                <time datetime="{{ $post->published_at?->toIso8601String() }}">
                    {{ $post->published_at?->format('Y-m-d H:i') }}
                </time>
            </div>

            <p class="mt-3 text-sm leading-relaxed text-gray-700">
                {{ Str::limit(strip_tags($post->body), 200) }}
            </p>

            <div class="mt-4">
                <a href="{{ route('posts.show', $post) }}"
                   class="text-sm font-medium text-gray-900 hover:underline">
                    Read more &rarr;
                </a>
            </div>
        </article>
    @empty
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
            <p class="text-gray-500">No posts yet.</p>
            <a href="{{ route('posts.create') }}"
               class="mt-4 inline-block rounded-md bg-gray-900 px-4 py-2 text-sm text-white hover:bg-gray-700">
                Write the first one
            </a>
        </div>
    @endforelse

    <div class="mt-8">
        {{ $posts->links() }}
    </div>
</x-layout>
