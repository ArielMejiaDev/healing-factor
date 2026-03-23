<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-Factor Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        (function () {
            var theme = localStorage.getItem('x-factor-theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: 'oklch(var(--primary) / <alpha-value>)',
                            foreground: 'oklch(var(--primary-foreground) / <alpha-value>)',
                        },
                        secondary: {
                            DEFAULT: 'oklch(var(--secondary) / <alpha-value>)',
                            foreground: 'oklch(var(--secondary-foreground) / <alpha-value>)',
                        },
                        accent: {
                            DEFAULT: 'oklch(var(--accent) / <alpha-value>)',
                            foreground: 'oklch(var(--accent-foreground) / <alpha-value>)',
                        },
                        muted: {
                            DEFAULT: 'oklch(var(--muted) / <alpha-value>)',
                            foreground: 'oklch(var(--muted-foreground) / <alpha-value>)',
                        },
                        background: 'oklch(var(--background) / <alpha-value>)',
                        foreground: 'oklch(var(--foreground) / <alpha-value>)',
                        card: {
                            DEFAULT: 'oklch(var(--card) / <alpha-value>)',
                            foreground: 'oklch(var(--card-foreground) / <alpha-value>)',
                        },
                        ring: 'oklch(var(--ring) / <alpha-value>)',
                        border: 'oklch(var(--border) / <alpha-value>)',
                        input: 'oklch(var(--input) / <alpha-value>)',
                    },
                },
            },
        }
    </script>
    <style>
        :root {
            --background: 1 0 0;
            --foreground: 0.145 0 0;
            --card: 1 0 0;
            --card-foreground: 0.145 0 0;
            --primary: 0.205 0 0;
            --primary-foreground: 0.985 0 0;
            --secondary: 0.97 0.001 286.375;
            --secondary-foreground: 0.205 0 0;
            --accent: 0.97 0.001 286.375;
            --accent-foreground: 0.205 0 0;
            --muted: 0.97 0.001 286.375;
            --muted-foreground: 0.556 0.016 286.375;
            --ring: 0.205 0 0;
            --border: 0.922 0 0;
            --input: 0.882 0 0;
        }
        .dark {
            --background: 0.145 0 0;
            --foreground: 0.985 0 0;
            --card: 0.145 0 0;
            --card-foreground: 0.985 0 0;
            --primary: 0.985 0 0;
            --primary-foreground: 0.205 0 0;
            --secondary: 0.269 0.001 286.375;
            --secondary-foreground: 0.985 0 0;
            --accent: 0.269 0.001 286.375;
            --accent-foreground: 0.985 0 0;
            --muted: 0.269 0.001 286.375;
            --muted-foreground: 0.708 0.016 286.375;
            --ring: 0.556 0 0;
            --border: 0.264 0 0;
            --input: 0.264 0 0;
        }
    </style>
</head>
<body class="bg-background text-foreground min-h-screen">
    <header class="border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('x-factor.dashboard.index') }}" class="text-xl font-semibold tracking-tight">X-Factor</a>
            <div class="flex items-center gap-2">
            <button
                id="poll-toggle"
                onclick="togglePolling()"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] bg-secondary text-secondary-foreground hover:bg-secondary/80 h-9 px-3 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0"
                aria-label="Toggle auto-refresh"
            >
                <svg id="poll-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span id="poll-label" class="text-xs hidden sm:inline">Auto-refresh</span>
            </button>
            <button
                onclick="toggleTheme()"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-all outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] bg-secondary text-secondary-foreground hover:bg-secondary/80 h-9 px-3 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0"
                aria-label="Toggle theme"
            >
                <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <script>
        function toggleTheme() {
            var html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('x-factor-theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('x-factor-theme', 'dark');
            }
        }

        (function () {
            var pollInterval = null;
            var btn = document.getElementById('poll-toggle');
            var icon = document.getElementById('poll-icon');
            var label = document.getElementById('poll-label');

            function applyActiveStyles() {
                btn.classList.remove('bg-secondary', 'text-secondary-foreground', 'hover:bg-secondary/80');
                btn.classList.add('bg-primary', 'text-primary-foreground', 'hover:bg-primary/90');
                icon.classList.add('animate-spin');
                label.textContent = 'Polling';
            }

            function applyInactiveStyles() {
                btn.classList.remove('bg-primary', 'text-primary-foreground', 'hover:bg-primary/90');
                btn.classList.add('bg-secondary', 'text-secondary-foreground', 'hover:bg-secondary/80');
                icon.classList.remove('animate-spin');
                label.textContent = 'Auto-refresh';
            }

            function startPolling() {
                pollInterval = setInterval(function () { location.reload(); }, 60000);
                localStorage.setItem('x-factor-poll', '1');
                applyActiveStyles();
            }

            function stopPolling() {
                clearInterval(pollInterval);
                pollInterval = null;
                localStorage.removeItem('x-factor-poll');
                applyInactiveStyles();
            }

            window.togglePolling = function () {
                if (pollInterval) {
                    stopPolling();
                } else {
                    startPolling();
                }
            };

            // Restore polling state across page loads
            if (localStorage.getItem('x-factor-poll') === '1') {
                startPolling();
            }
        })();

        // Close dropdown menus when clicking outside
        document.addEventListener('click', function (e) {
            document.querySelectorAll('[x-data] [role="menu"]').forEach(function (menu) {
                if (!menu.closest('[x-data]').contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
