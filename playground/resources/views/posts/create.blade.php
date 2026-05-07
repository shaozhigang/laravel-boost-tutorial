<x-layout>
    <x-slot:title>New Post</x-slot>

    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight">New Post</h1>
        <p class="mt-1 text-sm text-gray-500">Fill in the details below to publish a new post.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-medium">There were some problems with your submission:</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('posts.store') }}"
          class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf

        <x-posts.form-fields />

        <div class="mt-6 flex items-center gap-3">
            <button type="submit"
                    class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                Create post
            </button>
            <a href="{{ route('posts.index') }}"
               class="text-sm text-gray-600 hover:text-gray-900">
                Cancel
            </a>
        </div>
    </form>
</x-layout>
