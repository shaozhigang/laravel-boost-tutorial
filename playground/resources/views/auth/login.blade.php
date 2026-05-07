<x-layout>
    <x-slot:title>Login</x-slot>

    <div class="mx-auto max-w-md">
        <div class="mb-6">
            <h1 class="text-3xl font-bold tracking-tight">Login</h1>
            <p class="mt-1 text-sm text-gray-500">Sign in with your email and password.</p>
        </div>

        <form method="POST" action="{{ route('login') }}"
              class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" required autofocus
                       value="{{ old('email') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900">
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 block w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900">
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1"
                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                Remember me
            </label>

            <button type="submit"
                    class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                Log in
            </button>
        </form>
    </div>
</x-layout>
