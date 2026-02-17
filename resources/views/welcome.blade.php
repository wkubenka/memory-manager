<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col items-center gap-8 text-center">
                <x-app-logo-icon class="size-12 fill-current text-black dark:text-white" />

                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">{{ config('app.name') }}</h1>
                    <p class="text-neutral-500 dark:text-neutral-400">{{ __('Keep your notes and todos organized in one place.') }}</p>
                </div>

                <div class="flex gap-3">
                    @auth
                        <flux:button variant="primary" :href="route('notes.index')">{{ __('Go to Notes') }}</flux:button>
                    @else
                        <flux:button variant="primary" :href="route('login')">{{ __('Log in') }}</flux:button>
                        @if (Route::has('register'))
                            <flux:button variant="ghost" :href="route('register')">{{ __('Create account') }}</flux:button>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
