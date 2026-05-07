@props(['post' => null])

@php
    $titleValue = old('title', $post?->title);
    $slugValue = old('slug', $post?->slug);
    $bodyValue = old('body', $post?->body);
    $publishedAtValue = old(
        'published_at',
        $post?->published_at?->format('Y-m-d\TH:i')
    );
@endphp

<div class="space-y-5">
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
        <input type="text" name="title" id="title"
               value="{{ $titleValue }}"
               required maxlength="200"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 @error('title') border-red-500 @enderror">
        @error('title')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
        <input type="text" name="slug" id="slug"
               value="{{ $slugValue }}"
               required maxlength="255"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 @error('slug') border-red-500 @enderror">
        <p class="mt-1 text-xs text-gray-500">URL-friendly identifier, e.g. <code>my-first-post</code>.</p>
        @error('slug')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="body" class="block text-sm font-medium text-gray-700">Body</label>
        <textarea name="body" id="body" rows="10" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 @error('body') border-red-500 @enderror">{{ $bodyValue }}</textarea>
        @error('body')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="published_at" class="block text-sm font-medium text-gray-700">Published at</label>
        <input type="datetime-local" name="published_at" id="published_at"
               value="{{ $publishedAtValue }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 @error('published_at') border-red-500 @enderror">
        <p class="mt-1 text-xs text-gray-500">Leave empty to save as draft.</p>
        @error('published_at')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
