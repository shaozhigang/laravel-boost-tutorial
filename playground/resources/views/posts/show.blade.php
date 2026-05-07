<x-layout>
    <x-slot:title>{{ $post->title }}</x-slot>

    <article class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
        <header class="mb-6 border-b border-gray-100 pb-4">
            <h1 class="text-3xl font-bold tracking-tight">{{ $post->title }}</h1>

            <div class="mt-2 text-sm text-gray-500">
                <span>by {{ $post->author?->name ?? 'Unknown' }}</span>
                <span class="mx-1">&middot;</span>
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                        {{ $post->published_at->format('Y-m-d H:i') }}
                    </time>
                @else
                    <span class="italic">Draft</span>
                @endif
            </div>
        </header>

        <div class="prose max-w-none whitespace-pre-wrap text-base leading-relaxed text-gray-800">
            {{ $post->body }}
        </div>
    </article>

    <div class="mt-6 flex items-center gap-3">
        <a href="{{ route('posts.index') }}"
           class="text-sm text-gray-600 hover:text-gray-900">
            &larr; Back to posts
        </a>

        @can('update', $post)
            <a href="{{ route('posts.edit', $post) }}"
               class="ml-auto rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                Edit
            </a>
        @endcan

        @can('delete', $post)
            <form method="POST" action="{{ route('posts.destroy', $post) }}"
                  onsubmit="return confirm('Delete this post?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-500">
                    Delete
                </button>
            </form>
        @endcan
    </div>
</x-layout>
