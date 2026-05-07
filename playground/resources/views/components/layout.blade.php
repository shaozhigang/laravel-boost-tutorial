@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Blog') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <nav class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
            <a href="{{ route('posts.index') }}" class="text-lg font-semibold tracking-tight">
                {{ config('app.name', 'Blog') }}
            </a>

            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('posts.index') }}"
                   class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('posts.index') ? 'font-medium text-gray-900' : '' }}">
                    Posts
                </a>

                @auth
                    <a href="{{ route('posts.mine') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('posts.mine') ? 'font-medium text-gray-900' : '' }}">
                        My Posts
                    </a>
                    <a href="{{ route('posts.create') }}"
                       class="rounded-md bg-gray-900 px-3 py-1.5 text-white hover:bg-gray-700">
                        New Post
                    </a>
                    <span class="text-gray-500">Logged in as <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-gray-700 hover:bg-gray-100">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-md bg-gray-900 px-3 py-1.5 text-white hover:bg-gray-700">
                        Login
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-4xl px-6 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-4xl px-6 py-8 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} {{ config('app.name', 'Blog') }}
    </footer>
</body>
</html>
