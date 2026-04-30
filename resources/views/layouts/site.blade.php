<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Airlink Locate')</title>
        <meta name="description" content="@yield('meta_description', 'Localização em tempo real para acompanhar família e amigos com simplicidade e privacidade.')">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta name="theme-color" content="#09090b">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:100,200,300,400,500,600,700,800,900&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.materialdesignicons.com/7.4.47/css/materialdesignicons.min.css" />
        <link rel="icon" type="image/png" href="{{ asset('airlink-locate-logo-squad.png') }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Airlink Locate">
        <meta property="og:title" content="@yield('og_title', trim(strip_tags($__env->yieldContent('title', 'Airlink Locate'))))">
        <meta property="og:description" content="@yield('og_description', trim(strip_tags($__env->yieldContent('meta_description', 'Localização em tempo real para acompanhar família e amigos com simplicidade e privacidade.'))))">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="@yield('og_image', asset('airlink-locate-logo-squad.png'))">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="@yield('twitter_title', trim(strip_tags($__env->yieldContent('title', 'Airlink Locate'))))">
        <meta name="twitter:description" content="@yield('twitter_description', trim(strip_tags($__env->yieldContent('meta_description', 'Localização em tempo real para acompanhar família e amigos com simplicidade e privacidade.'))))">
        <meta name="twitter:image" content="@yield('twitter_image', asset('airlink-locate-logo-squad.png'))">

        @yield('seo')
        @stack('head')

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
                integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
            <style>
                :root {
                    --al-bg: #09090b;
                    --al-text: #f4f4f5;
                    --al-muted: rgba(244, 244, 245, 0.7);
                    --al-border: rgba(255, 255, 255, 0.12);
                    --al-surface: rgba(255, 255, 255, 0.06);
                    --al-surface-2: rgba(255, 255, 255, 0.085);
                    --al-radius: 1.25rem;
                }

                .al-navbar {
                    background: rgba(9, 9, 11, 0.7);
                    backdrop-filter: blur(14px);
                    -webkit-backdrop-filter: blur(14px);
                    border-bottom: 1px solid var(--al-border);
                }

                .al-card {
                    border: 1px solid var(--al-border);
                    background: var(--al-surface);
                    border-radius: var(--al-radius);
                }

                .al-card-strong {
                    background: var(--al-surface-2);
                }

                .al-btn-primary {
                    background: #ffffff;
                    color: #09090b;
                    border: 1px solid rgba(255, 255, 255, 0.9);
                    border-radius: 0.9rem;
                    padding: 0.75rem 1.05rem;
                    font-weight: 600;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .al-btn-secondary {
                    background: rgba(255, 255, 255, 0.08);
                    color: #ffffff;
                    border: 1px solid var(--al-border);
                    border-radius: 0.9rem;
                    padding: 0.75rem 1.05rem;
                    font-weight: 500;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .al-nav-btn {
                    border-radius: 999px;
                    padding: 0.55rem 1rem;
                    font-weight: 600;
                    font-size: 0.925rem;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .al-nav-btn-primary {
                    background: #ffffff;
                    color: #09090b;
                    border: 1px solid rgba(255, 255, 255, 0.9);
                }

                .al-nav-btn-secondary {
                    background: rgba(255, 255, 255, 0.06);
                    color: rgba(255, 255, 255, 0.92);
                    border: 1px solid var(--al-border);
                }

                .al-pill {
                    border: 1px solid var(--al-border);
                    background: rgba(255, 255, 255, 0.06);
                    border-radius: 999px;
                    backdrop-filter: blur(12px);
                    -webkit-backdrop-filter: blur(12px);
                }

                .al-hero {
                    min-height: 78vh;
                    display: flex;
                    align-items: center;
                }

                .al-hero-video {
                    position: absolute;
                    inset: 0;
                    z-index: 0;
                    overflow: hidden;
                    pointer-events: none;
                }

                .al-hero-video__media {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    object-position: center;
                    transform: scale(1.02);
                }

                .al-hero-overlay {
                    position: absolute;
                    inset: 0;
                    z-index: 1;
                    pointer-events: none;
                    background: radial-gradient(circle at 20% 15%, rgba(255, 255, 255, 0.08), transparent 55%),
                        linear-gradient(180deg, rgba(9, 9, 11, 0.18) 0%, rgba(9, 9, 11, 0.25) 35%, rgba(9, 9, 11, 0.72) 70%, rgba(9, 9, 11, 1) 100%);
                }

                .al-hero-content {
                    position: relative;
                    z-index: 2;
                }

                .al-section {
                    border-top: 1px solid rgba(255, 255, 255, 0.08);
                }

                .al-section-alt {
                    background: rgba(255, 255, 255, 0.03);
                }

                .al-icon {
                    height: 112px;
                    width: 112px;
                    margin: 0 auto;
                    border-radius: 28px;
                    border: 1px solid var(--al-border);
                    background: rgba(255, 255, 255, 0.06);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .al-icon i {
                    font-size: 42px;
                    line-height: 1;
                }

                .al-step-badge {
                    height: 42px;
                    width: 42px;
                    border-radius: 14px;
                    border: 1px solid var(--al-border);
                    background: rgba(255, 255, 255, 0.06);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    letter-spacing: -0.02em;
                }

                .al-oauth-card {
                    border: 1px solid var(--al-border);
                    background: rgba(255, 255, 255, 0.06);
                    border-radius: 1.5rem;
                    backdrop-filter: blur(16px);
                    -webkit-backdrop-filter: blur(16px);
                }

                .al-input {
                    background: rgba(255, 255, 255, 0.06);
                    border-color: var(--al-border);
                    color: var(--al-text);
                }

                .al-input:focus {
                    background: rgba(255, 255, 255, 0.08);
                    border-color: rgba(10, 132, 255, 0.7);
                    box-shadow: 0 0 0 0.25rem rgba(10, 132, 255, 0.18);
                    color: var(--al-text);
                }

                .al-footer {
                    background: #f1f1f3;
                    color: #111113;
                    border-top: 1px solid #e6e6ea;
                }
            </style>
            <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
                integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        @endif
    </head>
    <body>
        @php
            $isDashboard = request()->routeIs('dashboard');
            $isMapLike = $isDashboard;
        @endphp
        <div class="min-vh-100 d-flex flex-column">
            <header class="al-navbar navbar navbar-expand-lg sticky-top {{ $isMapLike ? 'al-navbar--transparent' : '' }}">
                <div class="{{ $isMapLike ? 'container-fluid px-3' : 'container' }} {{ $isMapLike ? 'py-1' : 'py-2' }}">
                    <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                        <img src="{{ asset('airlink-locate-logo-white.png') }}" alt="Airlink Locate" style="height: 28px; width: auto;">
                    </a>

                    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav"
                        aria-controls="siteNav" aria-expanded="false" aria-label="Alternar navegação">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="siteNav">
                        <div class="ms-auto d-flex gap-2 mt-3 mt-lg-0">
                            @auth
                                @php
                                    $sacratechUser = null;
                                    $fullName = auth()->user()?->name ?? '';
                                    $firstName = '';
                                    $lastName = '';

                                    if (auth()->user()?->sacratech_user_id) {
                                        $sacratechUser = app(\App\Services\SacratechAuthService::class)->fetchUserById((int) auth()->user()->sacratech_user_id);
                                    }

                                    if ($sacratechUser) {
                                        $firstName = trim((string) ($sacratechUser->nome ?? ''));
                                        $lastName = trim((string) ($sacratechUser->sobrenome ?? ''));
                                        $candidate = trim($firstName.' '.$lastName);
                                        if ($candidate !== '') {
                                            $fullName = $candidate;
                                        }
                                    }

                                    $photoRaw = is_string(auth()->user()?->photo) ? trim((string) auth()->user()->photo) : '';
                                    $photoUrl = '';
                                    if ($photoRaw !== '') {
                                        $photoUrl = preg_match('/^https?:\\/\\//i', $photoRaw) === 1 ? $photoRaw : asset('storage/'.$photoRaw);
                                    }
                                    $hasPhoto = $photoUrl !== '';

                                    $initials = '';
                                    $parts = array_values(array_filter(preg_split('/\\s+/', trim($fullName)) ?: []));
                                    if (count($parts) >= 2) {
                                        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1));
                                    } elseif (count($parts) === 1) {
                                        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
                                    }
                                @endphp

                                @if ($isDashboard)
                                    <button id="alFocusMe" type="button"
                                        class="al-nav-btn al-nav-btn-secondary d-inline-flex align-items-center gap-2 {{ $isMapLike ? 'al-nav-btn--compact' : '' }}"
                                        aria-label="Ir para minha localização">
                                        <i class="mdi mdi-crosshairs-gps"></i>
                                        <span class="d-none d-lg-inline">Minha localização</span>
                                    </button>
                                @endif

                                <div class="dropdown">
                                    <button class="al-nav-btn al-nav-btn-secondary dropdown-toggle d-inline-flex align-items-center gap-2 {{ $isMapLike ? 'al-nav-btn--compact' : '' }}"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <img id="alNavbarAvatarImg" src="{{ $photoUrl }}" alt="{{ $fullName }}"
                                            class="al-avatar-img {{ $hasPhoto ? '' : 'd-none' }}">
                                        <span id="alNavbarAvatarInitials" class="al-avatar-initials {{ $hasPhoto ? 'd-none' : '' }}">
                                            {{ $initials }}
                                        </span>
                                        <span class="d-none d-lg-inline">{{ $fullName }}</span>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end al-dropdown">
                                        <li>
                                            @if ($isDashboard)
                                                <button class="dropdown-item d-flex align-items-center gap-2" type="button" data-bs-toggle="modal"
                                                    data-bs-target="#settingsModal">
                                                    <i class="mdi mdi-cog-outline"></i>
                                                    Configurações
                                                </button>
                                            @else
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ url('/dashboard') }}">
                                                    <i class="mdi mdi-cog-outline"></i>
                                                    Configurações
                                                </a>
                                            @endif
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="https://account-id.sacratech.com" target="_blank"
                                                rel="noopener noreferrer">
                                                <img src="{{ asset('SacratechID-Icon.png') }}" alt="" style="height: 18px; width: auto;">
                                                Conta
                                                <i class="mdi mdi-open-in-new ms-auto opacity-75"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                    <i class="mdi mdi-logout-variant"></i>
                                                    Sair
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            @else
                                <a href="{{ route('login') }}" class="al-nav-btn al-nav-btn-primary {{ $isMapLike ? 'al-nav-btn--compact' : '' }}">
                                    Entrar
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-grow-1 {{ $isMapLike ? 'p-0' : '' }}">
                @yield('content')
            </main>

            @if (! $isMapLike)
                @include('partials.footer')
            @else
                <div class="al-dashboard-footer small text-secondary">
                    © {{ now()->year }} Sacratech Softwares LTDA.
                </div>
            @endif
        </div>
    </body>
</html>
