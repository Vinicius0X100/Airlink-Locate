@extends('layouts.site')

@section('title', 'Painel - Airlink Locate')

@push('head')
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.css" rel="stylesheet">
    <script defer src="https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.js"></script>
@endpush

@section('content')
    @php
        $meName = (string) (auth()->user()?->name ?? '');
        $mePhotoRaw = is_string(auth()->user()?->photo) ? trim((string) auth()->user()->photo) : '';
        $mePhoto = '';

        if ($mePhotoRaw !== '') {
            $mePhoto = preg_match('/^https?:\\/\\//i', $mePhotoRaw) === 1 ? $mePhotoRaw : asset('storage/'.$mePhotoRaw);
        }

        $parts = array_values(array_filter(preg_split('/\\s+/', trim($meName)) ?: []));
        if (count($parts) >= 2) {
            $meInitials = mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1));
        } elseif (count($parts) === 1) {
            $meInitials = mb_strtoupper(mb_substr($parts[0], 0, 1));
        } else {
            $meInitials = '';
        }

        $meDevice = \App\Models\Device::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('last_seen_at')
            ->first();

        $families = auth()->user()
            ?->families()
            ->with('users:id,name,photo')
            ->withCount('users')
            ->orderBy('name')
            ->get() ?? collect();

        $circles = auth()->user()
            ?->circles()
            ->with('users:id,name,photo')
            ->withCount('users')
            ->orderBy('name')
            ->get() ?? collect();

        $connections = \App\Models\UserConnection::query()
            ->where('status', 'accepted')
            ->where(function ($q) {
                $q->where('user_a_id', auth()->id())->orWhere('user_b_id', auth()->id());
            })
            ->with(['userA', 'userB'])
            ->get();

        $safePlaces = auth()->user()
            ?->safePlaces()
            ->orderByDesc('created_at')
            ->get() ?? collect();
    @endphp

    <div class="al-map-shell">
        <div id="map" class="al-map-canvas" aria-label="Mapa do Airlink Locate"
            data-mapbox-token="{{ (string) config('mapbox.token') }}"
            data-mapbox-style="{{ (string) config('mapbox.style') }}"
            data-mapbox-center-lng="{{ is_null($meDevice?->last_lng) ? -46.6333 : (float) $meDevice->last_lng }}"
            data-mapbox-center-lat="{{ is_null($meDevice?->last_lat) ? -23.5505 : (float) $meDevice->last_lat }}"
            data-mapbox-zoom="{{ is_null($meDevice?->last_lat) || is_null($meDevice?->last_lng) ? 12 : 15 }}"
            data-me-user-id="{{ (string) auth()->id() }}"
            data-me-name="{{ $meName }}"
            data-me-photo="{{ $mePhoto }}"
            data-me-initials="{{ $meInitials }}"
            data-me-share-location="{{ auth()->user()?->share_location ? '1' : '0' }}"
            data-location-required-url="{{ route('location.required') }}"
            data-me-lat="{{ $meDevice?->last_lat }}"
            data-me-lng="{{ $meDevice?->last_lng }}"></div>

        <div class="al-map-detail" id="alMapDetail" aria-hidden="true">
            <div class="al-map-detail__head">
                <div class="al-map-detail__avatar" id="alMapDetailAvatar"></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-white text-truncate" id="alMapDetailTitle"></div>
                    <div class="text-secondary small text-truncate" id="alMapDetailSubtitle"></div>
                </div>
                <button class="al-map-detail__close" type="button" id="alMapDetailClose" aria-label="Fechar">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="al-map-detail__meta" id="alMapDetailMeta"></div>
            <div class="al-map-detail__actions" id="alMapDetailActions"></div>
        </div>

        <div class="modal fade" id="routeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm modal-dialog-scrollable">
                <div class="modal-content al-card">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-semibold">Iniciar rota</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="text-secondary small mb-3" id="routeModalSubtitle"></div>
                        <div class="vstack gap-2">
                            <a class="al-btn-primary text-decoration-none" href="#" id="routeOpenGoogle" target="_blank"
                                rel="noopener noreferrer">Google Maps</a>
                            <a class="al-btn-secondary text-decoration-none" href="#" id="routeOpenWaze" target="_blank"
                                rel="noopener noreferrer">Waze</a>
                            <a class="al-btn-secondary text-decoration-none" href="#" id="routeOpenApple" target="_blank"
                                rel="noopener noreferrer">Apple Maps</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="al-loading-screen" id="alLoadingScreen" aria-live="polite" aria-busy="true">
            <div class="al-loading-screen__content">
                <img class="al-loading-screen__logo" src="{{ asset('airlink-locate-logo-white.png') }}" alt="Airlink Locate">
                <div class="al-loading-screen__spinner" aria-hidden="true"></div>
                <div class="al-loading-screen__text" id="alLoadingText">Carregando…</div>
                <div class="al-loading-screen__brand">
                    <img src="{{ asset('Sacratech_white.png') }}" alt="Sacratech" class="al-loading-screen__brand-logo">
                </div>
            </div>
        </div>

        <div class="al-hub">
            <div class="al-hub__panel al-card al-card-strong p-3 p-md-4">
                <div class="al-hub__header d-flex align-items-center justify-content-between mb-3">
                    <div class="al-hub__brand d-flex align-items-center gap-2">
                        <i class="mdi mdi-map-marker-radius fs-4"></i>
                        <div>
                            <div class="al-hub__title fw-semibold">Airlink Locate</div>
                            <div class="al-hub__subtitle text-secondary small">Hub</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill text-bg-light text-dark">Beta</span>
                        <button class="al-hub__collapse-btn" type="button" id="hubToggle" aria-label="Minimizar hub">
                            <i class="mdi mdi-chevron-down"></i>
                        </button>
                    </div>
                </div>

                <div class="al-hub__panel-body">
                    <div class="al-hub__search mb-3">
                        <i class="mdi mdi-magnify"></i>
                        <input class="form-control al-input al-hub__search-input" type="text" placeholder="Buscar pessoas, grupos e locais">
                    </div>

                    @if (! config('mapbox.token'))
                        <div class="alert alert-warning small mb-3">
                            Configure MAPBOX_ACCESS_TOKEN no .env para o mapa carregar.
                        </div>
                    @endif

                    <div class="vstack gap-2">
                        <button class="al-btn-secondary w-100 justify-content-start" type="button" data-bs-toggle="modal"
                            data-bs-target="#familiesModal">
                            <i class="mdi mdi-account-group-outline me-2"></i>
                            Famílias
                        </button>
                        <button class="al-btn-secondary w-100 justify-content-start" type="button" data-bs-toggle="modal"
                            data-bs-target="#circlesModal">
                            <i class="mdi mdi-circle-outline me-2"></i>
                            Círculos
                        </button>
                        <button class="al-btn-secondary w-100 justify-content-start" type="button" data-bs-toggle="modal"
                            data-bs-target="#connectionsModal">
                            <i class="mdi mdi-link-variant me-2"></i>
                            Conexões
                        </button>
                        <button class="al-btn-secondary w-100 justify-content-start" type="button" data-bs-toggle="modal"
                            data-bs-target="#safePlacesModal">
                            <i class="mdi mdi-home-map-marker me-2"></i>
                            Locais seguros
                        </button>
                    </div>
                </div>
            </div>

            <div class="al-hub__dock al-card al-card-strong px-2 py-2">
                <button class="al-hub__dock-btn" type="button" aria-label="Mapa">
                    <i class="mdi mdi-map-outline"></i>
                </button>
                <button class="al-hub__dock-btn" type="button" aria-label="Pessoas" data-bs-toggle="modal"
                    data-bs-target="#connectionsModal">
                    <i class="mdi mdi-account-multiple-outline"></i>
                </button>
                <button class="al-hub__dock-btn al-hub__dock-btn--notif" type="button" aria-label="Alertas" data-bs-toggle="modal"
                    data-bs-target="#alertsModal">
                    <i class="mdi mdi-bell-outline"></i>
                    <span class="al-notif-badge d-none" id="alAlertsBadge">0</span>
                </button>
                @php
                    $dockConnections = $connections
                        ->map(function ($c) {
                            return $c->user_a_id === auth()->id() ? $c->userB : $c->userA;
                        })
                        ->filter()
                        ->unique('id')
                        ->take(7)
                        ->values();

                    $makeInitials = function (?string $name): string {
                        $name = trim((string) $name);
                        if ($name === '') {
                            return '?';
                        }
                        $parts = preg_split('/\s+/', $name) ?: [];
                        $first = $parts[0] ?? '';
                        $last = count($parts) > 1 ? ($parts[count($parts) - 1] ?? '') : '';
                        $initials = mb_substr($first, 0, 1).mb_substr($last, 0, 1);
                        $initials = trim($initials);
                        return $initials !== '' ? mb_strtoupper($initials) : mb_strtoupper(mb_substr($name, 0, 2));
                    };
                @endphp
                @if ($dockConnections->isNotEmpty())
                    <div class="al-hub__dock-connections" id="alDockConnections" aria-label="Conexões recentes">
                        @foreach ($dockConnections as $u)
                            <button class="al-hub__dock-avatar" type="button" data-user-id="{{ (int) $u->id }}"
                                aria-label="{{ (string) ($u->name ?? 'Conexão') }}">
                                @if ($u->photo)
                                    <img class="al-avatar-img" src="{{ asset('storage/' . $u->photo) }}" alt="">
                                @else
                                    <span class="al-avatar-initials">{{ $makeInitials($u->name ?? $u->email) }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="familiesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content al-card">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold">Famílias</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body pt-0">
                    <form method="POST" action="{{ route('families.store') }}" class="d-flex gap-2 mb-3" id="familiesCreateForm">
                        @csrf
                        <input class="form-control al-input" type="text" name="name" placeholder="Nome da família" maxlength="120" required>
                        <button class="al-btn-primary" type="submit">Criar</button>
                    </form>
                    <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                        <div class="fw-semibold text-white mb-2">Convidar por link</div>
                        <form class="vstack gap-2" id="familyInviteForm">
                            <div class="d-flex gap-2">
                                <select class="form-select al-input" name="family_id" id="familyInviteFamilyId"
                                    {{ $families->isEmpty() ? 'disabled' : '' }} required>
                                    @foreach ($families as $family)
                                        <option value="{{ (int) $family->id }}">{{ $family->name }}</option>
                                    @endforeach
                                </select>
                                <input class="form-control al-input" type="email" name="email" placeholder="Email Sacratech iD"
                                    {{ $families->isEmpty() ? 'disabled' : '' }} required>
                            </div>
                            <div class="d-flex gap-2">
                                <select class="form-select al-input" id="familyInviteConnectionPick"
                                    {{ $connections->isEmpty() ? 'disabled' : '' }}>
                                    <option value="">Selecionar conexão…</option>
                                    @foreach ($connections as $c)
                                        @php
                                            $other = $c->user_a_id === auth()->id() ? $c->userB : $c->userA;
                                        @endphp
                                        @if ($other?->email)
                                            <option value="{{ (string) $other->email }}" data-user-id="{{ (int) $other->id }}">
                                                {{ (string) ($other->name ?? $other->email) }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <button class="al-btn-secondary" type="button" id="familyInviteUseConnection"
                                    {{ $connections->isEmpty() ? 'disabled' : '' }}>
                                    Usar
                                </button>
                            </div>
                            <button class="al-btn-primary" type="submit" id="familyInviteSubmit" {{ $families->isEmpty() ? 'disabled' : '' }}>
                                Gerar
                            </button>
                        </form>
                        <div class="mt-3 d-none" id="familyInviteResult">
                            <div class="text-secondary small mb-2">Link gerado</div>
                            <div class="d-flex gap-2">
                                <input class="form-control al-input" type="text" id="familyInviteUrl" readonly>
                                <button class="al-btn-secondary" type="button" id="copyFamilyInvite">Copiar</button>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2 d-none" id="familyInviteError"></div>
                    </div>
                    @if ($families->isEmpty())
                        <div class="text-secondary">Nenhuma família ainda.</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($families as $family)
                                <div class="list-group-item bg-transparent text-white border-secondary border-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $family->name }}</div>
                                            <div class="text-secondary small">{{ $family->users_count }} pessoa(s)</div>
                                        </div>
                                        <button class="al-btn-secondary" type="button" data-al-action="open-family"
                                            data-family-id="{{ (int) $family->id }}" data-family-name="{{ (string) $family->name }}"
                                            data-member-ids="{{ $family->users->pluck('id')->implode(',') }}">
                                            Abrir
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="circlesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content al-card">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold">Círculos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body pt-0">
                    <form method="POST" action="{{ route('circles.store') }}" class="d-flex gap-2 mb-3" id="circlesCreateForm">
                        @csrf
                        <input class="form-control al-input" type="text" name="name" placeholder="Nome do círculo" maxlength="120" required>
                        <button class="al-btn-primary" type="submit">Criar</button>
                    </form>
                    <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                        <div class="fw-semibold text-white mb-2">Convidar por link</div>
                        <form class="vstack gap-2" id="circleInviteForm">
                            <div class="d-flex gap-2">
                                <select class="form-select al-input" name="circle_id" id="circleInviteCircleId"
                                    {{ $circles->isEmpty() ? 'disabled' : '' }} required>
                                    @foreach ($circles as $circle)
                                        <option value="{{ (int) $circle->id }}">{{ $circle->name }}</option>
                                    @endforeach
                                </select>
                                <input class="form-control al-input" type="email" name="email" placeholder="Email Sacratech iD"
                                    {{ $circles->isEmpty() ? 'disabled' : '' }} required>
                            </div>
                            <div class="d-flex gap-2">
                                <select class="form-select al-input" id="circleInviteConnectionPick"
                                    {{ $connections->isEmpty() ? 'disabled' : '' }}>
                                    <option value="">Selecionar conexão…</option>
                                    @foreach ($connections as $c)
                                        @php
                                            $other = $c->user_a_id === auth()->id() ? $c->userB : $c->userA;
                                        @endphp
                                        @if ($other?->email)
                                            <option value="{{ (string) $other->email }}" data-user-id="{{ (int) $other->id }}">
                                                {{ (string) ($other->name ?? $other->email) }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <button class="al-btn-secondary" type="button" id="circleInviteUseConnection"
                                    {{ $connections->isEmpty() ? 'disabled' : '' }}>
                                    Usar
                                </button>
                            </div>
                            <button class="al-btn-primary" type="submit" id="circleInviteSubmit" {{ $circles->isEmpty() ? 'disabled' : '' }}>
                                Gerar
                            </button>
                        </form>
                        <div class="mt-3 d-none" id="circleInviteResult">
                            <div class="text-secondary small mb-2">Link gerado</div>
                            <div class="d-flex gap-2">
                                <input class="form-control al-input" type="text" id="circleInviteUrl" readonly>
                                <button class="al-btn-secondary" type="button" id="copyCircleInvite">Copiar</button>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2 d-none" id="circleInviteError"></div>
                    </div>
                    @if ($circles->isEmpty())
                        <div class="text-secondary">Nenhum círculo ainda.</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($circles as $circle)
                                <div class="list-group-item bg-transparent text-white border-secondary border-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $circle->name }}</div>
                                            <div class="text-secondary small">{{ $circle->users_count }} pessoa(s)</div>
                                        </div>
                                        <button class="al-btn-secondary" type="button" data-al-action="open-circle"
                                            data-circle-id="{{ (int) $circle->id }}" data-circle-name="{{ (string) $circle->name }}"
                                            data-member-ids="{{ $circle->users->pluck('id')->implode(',') }}">
                                            Abrir
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="groupMembersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content al-card">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold" id="groupMembersTitle">Membros</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="list-group list-group-flush" id="groupMembersList"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="connectionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content al-card">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold">Conexões</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                        <div class="fw-semibold text-white mb-2">Convidar por link</div>
                        <form class="d-flex gap-2" id="connectionInviteForm">
                            <input class="form-control al-input" type="email" name="email" placeholder="Email Sacratech iD" required>
                            <button class="al-btn-primary" type="submit">Gerar</button>
                        </form>
                        <div class="mt-3 d-none" id="connectionInviteResult">
                            <div class="text-secondary small mb-2">Link gerado</div>
                            <div class="d-flex gap-2">
                                <input class="form-control al-input" type="text" id="connectionInviteUrl" readonly>
                                <button class="al-btn-secondary" type="button" id="copyConnectionInvite">Copiar</button>
                            </div>
                        </div>
                        <div class="text-secondary small mt-2 d-none" id="connectionInviteError"></div>
                    </div>
                    @if ($connections->isEmpty())
                        <div class="text-secondary">Nenhuma conexão aceita ainda.</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($connections as $c)
                                @php
                                    $other = $c->user_a_id === auth()->id() ? $c->userB : $c->userA;
                                    $otherName = $other?->name ?? 'Usuário';
                                    $otherEmail = $other?->email ?? '';
                                @endphp
                                <div class="list-group-item bg-transparent text-white border-secondary border-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $otherName }}</div>
                                            <div class="text-secondary small">
                                                Compartilhamento: {{ $c->share_location ? 'ativado' : 'desativado' }}
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button class="al-btn-secondary" type="button" data-al-invite-kind="family"
                                                data-al-invite-email="{{ (string) $otherEmail }}" {{ $otherEmail === '' ? 'disabled' : '' }}>
                                                Família
                                            </button>
                                            <button class="al-btn-secondary" type="button" data-al-invite-kind="circle"
                                                data-al-invite-email="{{ (string) $otherEmail }}" {{ $otherEmail === '' ? 'disabled' : '' }}>
                                                Círculo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="al-dock-sheet" id="alDockPersonSheet" aria-hidden="true">
        <div class="al-dock-sheet__card al-card">
            <div class="al-dock-sheet__head">
                <div class="al-dock-sheet__avatar" id="alDockPersonAvatar"></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold text-white text-truncate" id="alDockPersonName">Conexão</div>
                    <div class="text-secondary small text-truncate" id="alDockPersonStatus"></div>
                </div>
                <button class="al-dock-sheet__close" type="button" id="alDockPersonClose" aria-label="Fechar">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="al-dock-sheet__body">
                <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                    <div class="fw-semibold text-white mb-2">Grupos em comum</div>
                    <div class="text-secondary small" id="alDockPersonGroups"></div>
                </div>
                <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                    <div class="fw-semibold text-white mb-2">Locais seguros</div>
                    <div class="text-secondary small mb-2" id="alDockPersonPlacesHint"></div>
                    <div class="list-group list-group-flush" id="alDockPersonPlaces"></div>
                </div>
                <div class="d-flex gap-2">
                    <button class="al-btn-primary flex-grow-1" type="button" id="alDockPersonGo">Ir até a pessoa</button>
                    <button class="al-btn-secondary flex-grow-1" type="button" id="alDockPersonRoute">Traçar rota</button>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button class="al-btn-secondary flex-grow-1" type="button" id="alDockPersonStartRoute">Iniciar rota</button>
                    <button class="al-btn-secondary flex-grow-1" type="button" id="alDockPersonClose2">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="al-route-bar d-none" id="alRouteBar" aria-hidden="true">
        <div class="al-route-bar__label text-truncate" id="alRouteBarLabel">Rota</div>
        <button class="al-route-bar__btn" type="button" id="alRouteBarClear">Encerrar rota</button>
    </div>

    <div class="modal fade al-modal-side" id="safePlacesModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog-scrollable al-modal-side__dialog">
            <div class="modal-content al-card">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold">Locais seguros</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                        <div class="fw-semibold text-white mb-2">Novo local seguro</div>
                        <form method="POST" action="{{ route('safe_places.store') }}" id="safePlaceForm" class="vstack gap-2" data-ajax="1">
                            @csrf
                            <input class="form-control al-input" type="text" name="name" placeholder="Nome (ex: Casa, Trabalho)" maxlength="120"
                                required>
                            <select class="form-select al-input" name="icon" required>
                                <option value="mdi-home-map-marker">Casa</option>
                                <option value="mdi-briefcase">Trabalho</option>
                                <option value="mdi-school">Faculdade/Escola</option>
                                <option value="mdi-hospital-box-outline">Hospital</option>
                                <option value="mdi-map-marker">Local</option>
                                <option value="mdi-star-outline">Favorito</option>
                            </select>
                            <div class="al-field">
                                <input class="form-control al-input" type="text" name="address" id="safePlaceAddress"
                                    placeholder="Endereço (ex: Av. Paulista, 1000 - São Paulo)" autocomplete="off" required>
                                <div class="al-address-results d-none" id="safePlaceAddressResults" role="listbox"></div>
                            </div>
                            <input type="hidden" name="lat" id="safePlaceLat" required>
                            <input type="hidden" name="lng" id="safePlaceLng" required>
                            <div class="al-range">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="text-secondary small">Área segura</div>
                                    <div class="text-white small fw-semibold"><span id="safePlaceRadiusValue">150</span> m</div>
                                </div>
                                <input class="form-range" type="range" name="radius" id="safePlaceRadius" min="25" max="2000" value="150" step="5"
                                    required>
                            </div>
                            <div class="text-secondary small" id="safePlaceHint">
                                Digite e selecione um endereço para posicionar o local no mapa. A área segura aparece no mapa enquanto você ajusta.
                            </div>
                            <button class="al-btn-primary" type="submit" id="safePlaceSubmit">Salvar local</button>
                        </form>
                    </div>
                    @if ($safePlaces->isEmpty())
                        <div class="text-secondary">Nenhum local seguro cadastrado.</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($safePlaces as $p)
                                <div class="list-group-item bg-transparent text-white border-secondary border-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $p->name }}</div>
                                            <div class="text-secondary small">
                                                Raio: {{ (int) $p->radius }}m
                                            </div>
                                        </div>
                                        <button class="al-btn-secondary" type="button" data-al-action="view-safe-place"
                                            data-safe-place-id="{{ (int) $p->id }}" data-safe-place-name="{{ (string) $p->name }}"
                                            data-safe-place-icon="{{ (string) ($p->icon ?: 'mdi-home-map-marker') }}"
                                            data-safe-place-address="{{ (string) ($p->address ?: '') }}"
                                            data-safe-place-lat="{{ (float) $p->latitude }}" data-safe-place-lng="{{ (float) $p->longitude }}"
                                            data-safe-place-radius="{{ (int) $p->radius }}">
                                            Ver
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="alertsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content al-card">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold">Alertas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <div class="text-secondary small" id="alAlertsSubtitle"></div>
                        <button class="al-btn-secondary" type="button" id="alAlertsMarkAll">Marcar tudo como visto</button>
                    </div>
                    <div class="list-group list-group-flush" id="alAlertsList"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content al-card">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-semibold">Configurações</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                        <div class="fw-semibold text-white mb-2">Foto de perfil</div>
                        <form method="POST" action="{{ route('me.photo') }}" enctype="multipart/form-data" id="mePhotoForm" data-ajax="1">
                            @csrf
                            <input class="d-none" type="file" id="mePhotoInput" name="photo" accept="image/*" required>

                            <div class="d-flex align-items-center gap-3">
                                <div class="al-profile-circle" id="mePhotoPreview">
                                    @if ($mePhoto)
                                        <img src="{{ $mePhoto }}" alt="{{ $meName }}" class="al-profile-circle__img">
                                    @else
                                        <span class="al-profile-circle__initials">{{ $meInitials }}</span>
                                    @endif
                                </div>

                                <label class="al-dropzone flex-grow-1" for="mePhotoInput" id="mePhotoDropzone">
                                    <div class="fw-semibold text-white">Arraste e solte</div>
                                    <div class="text-secondary small">ou clique para selecionar</div>
                                </label>

                                <button class="al-btn-primary" type="submit" id="mePhotoSubmit">Salvar</button>
                            </div>
                        </form>
                        <div class="text-secondary small mt-2">
                            A foto fica armazenada localmente no servidor (storage público) para outras pessoas verem no mapa.
                        </div>
                    </div>

                    <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                        <div class="fw-semibold text-white mb-1">Localização</div>
                        <div class="text-secondary small">
                            Você pode ajustar a permissão de localização no navegador/dispositivo. O Airlink Locate usa localização para mapa, atualizações e
                            alertas.
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-3 mt-3">
                            <div>
                                <div class="text-white fw-semibold">Ocultar meus passos</div>
                                <div class="text-secondary small">Pausa o compartilhamento em tempo real. As pessoas veem sua última localização.</div>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="alHideSteps"
                                    {{ auth()->user()?->share_location ? '' : 'checked' }}>
                            </div>
                        </div>
                    </div>

                    <div class="al-card al-card-strong p-3 p-md-4">
                        <div class="fw-semibold text-white mb-2">Conta</div>
                        <a class="al-btn-secondary text-decoration-none w-100 d-inline-flex align-items-center justify-content-center gap-2"
                            href="https://account-id.sacratech.com" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset('SacratechID-Icon.png') }}" alt="" style="height: 18px; width: auto;">
                            Abrir Sacratech iD
                            <i class="mdi mdi-open-in-new"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const hub = document.querySelector('.al-hub');
            const toggle = document.getElementById('hubToggle');
            const storageKey = 'airlink_hub_collapsed';

            const apply = (collapsed) => {
                if (!hub) return;
                hub.classList.toggle('al-hub--collapsed', collapsed);
                if (toggle) toggle.setAttribute('aria-label', collapsed ? 'Expandir hub' : 'Minimizar hub');
            };

            const initial = localStorage.getItem(storageKey) === '1';
            apply(initial);

            toggle?.addEventListener('click', () => {
                const next = !hub?.classList.contains('al-hub--collapsed');
                localStorage.setItem(storageKey, next ? '1' : '0');
                apply(next);
            });

            const form = document.getElementById('connectionInviteForm');
            const resultWrap = document.getElementById('connectionInviteResult');
            const urlInput = document.getElementById('connectionInviteUrl');
            const copyBtn = document.getElementById('copyConnectionInvite');
            const err = document.getElementById('connectionInviteError');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const familyMemberIds = @json($families->mapWithKeys(fn ($f) => [(string) $f->id => $f->users->pluck('id')->map(fn ($id) => (string) $id)->values()])->all());
            const circleMemberIds = @json($circles->mapWithKeys(fn ($c) => [(string) $c->id => $c->users->pluck('id')->map(fn ($id) => (string) $id)->values()])->all());

            const showError = (message) => {
                if (!err) return;
                err.textContent = message;
                err.classList.remove('d-none');
            };

            const clearError = () => {
                if (!err) return;
                err.textContent = '';
                err.classList.add('d-none');
            };

            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearError();

                const fd = new FormData(form);
                const email = String(fd.get('email') || '').trim();
                if (!email) return;

                try {
                    const res = await fetch('{{ route('connections.invite') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ email }),
                    });

                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        showError(data?.message || 'Não foi possível gerar o convite.');
                        return;
                    }

                    if (urlInput) urlInput.value = String(data?.url || '');
                    if (resultWrap) resultWrap.classList.remove('d-none');
                } catch {
                    showError('Não foi possível gerar o convite.');
                }
            });

            copyBtn?.addEventListener('click', async () => {
                const value = String(urlInput?.value || '');
                if (!value) return;
                try {
                    await navigator.clipboard.writeText(value);
                } catch {
                }
            });

            const setupGroupInvite = ({ formId, selectId, urlInputId, resultId, errorId, copyId, endpointForId }) => {
                const f = document.getElementById(formId);
                const select = document.getElementById(selectId);
                const result = document.getElementById(resultId);
                const urlEl = document.getElementById(urlInputId);
                const copy = document.getElementById(copyId);
                const error = document.getElementById(errorId);

                const showErr = (message) => {
                    if (!error) return;
                    error.textContent = message;
                    error.classList.remove('d-none');
                };

                const clearErr = () => {
                    if (!error) return;
                    error.textContent = '';
                    error.classList.add('d-none');
                };

                f?.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    clearErr();
                    if (!select) return;

                    const submit = f.querySelector('button[type="submit"]');
                    setButtonLoading(submit, true);

                    const fd = new FormData(f);
                    const email = String(fd.get('email') || '').trim();
                    const id = String(select.value || '').trim();
                    if (!email || !id) {
                        setButtonLoading(submit, false);
                        return;
                    }

                    try {
                        const res = await fetch(endpointForId(id), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Content-Type': 'application/json',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ email }),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            showErr(data?.message || 'Não foi possível gerar o convite.');
                            return;
                        }

                        if (urlEl) urlEl.value = String(data?.url || '');
                        if (result) result.classList.remove('d-none');
                    } catch {
                        showErr('Não foi possível gerar o convite.');
                    } finally {
                        setButtonLoading(submit, false);
                    }
                });

                copy?.addEventListener('click', async () => {
                    const value = String(urlEl?.value || '');
                    if (!value) return;
                    try {
                        await navigator.clipboard.writeText(value);
                    } catch {
                    }
                });

                return {
                    addOption: ({ id, name }) => {
                        if (!select) return;
                        const opt = document.createElement('option');
                        opt.value = String(id);
                        opt.textContent = String(name || '');
                        select.appendChild(opt);
                        select.disabled = false;
                        f?.querySelector('input[name="email"]')?.removeAttribute('disabled');
                        f?.querySelector('button[type="submit"]')?.removeAttribute('disabled');
                    },
                };
            };

            const buildConnectionOptions = (pick) => {
                if (!pick) return [];
                return [...pick.querySelectorAll('option')]
                    .slice(1)
                    .map((o) => ({
                        value: String(o.value || '').trim(),
                        label: String(o.textContent || '').trim(),
                        userId: o.dataset.userId ? String(o.dataset.userId) : '',
                    }))
                    .filter((x) => x.value && x.userId);
            };

            const renderConnectionPick = (pick, all, excludedIds) => {
                if (!pick) return;
                const keep = new Set((excludedIds || []).map((x) => String(x)));
                const current = String(pick.value || '').trim();

                pick.innerHTML = '<option value="">Selecionar conexão…</option>';
                all.forEach((it) => {
                    if (!it.userId || keep.has(String(it.userId))) return;
                    const opt = document.createElement('option');
                    opt.value = it.value;
                    opt.textContent = it.label;
                    opt.dataset.userId = it.userId;
                    pick.appendChild(opt);
                });

                if (current && [...pick.options].some((o) => o.value === current)) {
                    pick.value = current;
                } else {
                    pick.value = '';
                }
            };

            const familyInvite = setupGroupInvite({
                formId: 'familyInviteForm',
                selectId: 'familyInviteFamilyId',
                urlInputId: 'familyInviteUrl',
                resultId: 'familyInviteResult',
                errorId: 'familyInviteError',
                copyId: 'copyFamilyInvite',
                endpointForId: (id) => `{{ url('/families') }}/${encodeURIComponent(id)}/invite`,
            });

            const circleInvite = setupGroupInvite({
                formId: 'circleInviteForm',
                selectId: 'circleInviteCircleId',
                urlInputId: 'circleInviteUrl',
                resultId: 'circleInviteResult',
                errorId: 'circleInviteError',
                copyId: 'copyCircleInvite',
                endpointForId: (id) => `{{ url('/circles') }}/${encodeURIComponent(id)}/invite`,
            });

            const openInviteForEmail = ({ kind, email }) => {
                const safeEmail = String(email || '').trim();
                if (!safeEmail) return;

                const openModal = (id) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    window.bootstrap?.Modal?.getOrCreateInstance(el)?.show();
                };

                const closeModal = (id) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    window.bootstrap?.Modal?.getOrCreateInstance(el)?.hide();
                };

                closeModal('connectionsModal');

                if (kind === 'family') {
                    openModal('familiesModal');
                    window.setTimeout(() => {
                        const form = document.getElementById('familyInviteForm');
                        const input = form?.querySelector('input[name="email"]');
                        if (input) {
                            input.value = safeEmail;
                            input.focus();
                            input.select?.();
                        }
                        form?.scrollIntoView?.({ block: 'center', behavior: 'smooth' });
                    }, 120);
                }

                if (kind === 'circle') {
                    openModal('circlesModal');
                    window.setTimeout(() => {
                        const form = document.getElementById('circleInviteForm');
                        const input = form?.querySelector('input[name="email"]');
                        if (input) {
                            input.value = safeEmail;
                            input.focus();
                            input.select?.();
                        }
                        form?.scrollIntoView?.({ block: 'center', behavior: 'smooth' });
                    }, 120);
                }
            };

            document.addEventListener('click', (e) => {
                const btn = e.target?.closest?.('[data-al-invite-kind]');
                if (!btn) return;
                e.preventDefault();
                const kind = btn.dataset.alInviteKind ? String(btn.dataset.alInviteKind) : '';
                const email = btn.dataset.alInviteEmail ? String(btn.dataset.alInviteEmail) : '';
                openInviteForEmail({ kind, email });
            });

            const bindConnectionPicker = ({ pickId, useId, formId }) => {
                const pick = document.getElementById(pickId);
                const use = document.getElementById(useId);
                const form = document.getElementById(formId);
                const input = form?.querySelector?.('input[name="email"]');
                if (!pick || !form || !input) return;

                const apply = () => {
                    const email = String(pick.value || '').trim();
                    if (!email) return;
                    input.value = email;
                    input.focus?.();
                    input.select?.();
                };

                use?.addEventListener('click', (e) => {
                    e.preventDefault();
                    apply();
                });

                pick.addEventListener('change', () => apply());
            };

            const familyPick = document.getElementById('familyInviteConnectionPick');
            const circlePick = document.getElementById('circleInviteConnectionPick');
            const allFamilyConnections = buildConnectionOptions(familyPick);
            const allCircleConnections = buildConnectionOptions(circlePick);

            const familyIdSelect = document.getElementById('familyInviteFamilyId');
            const circleIdSelect = document.getElementById('circleInviteCircleId');

            const updateFamilyPick = () => {
                const id = familyIdSelect ? String(familyIdSelect.value || '') : '';
                renderConnectionPick(familyPick, allFamilyConnections, familyMemberIds?.[id] || []);
            };

            const updateCirclePick = () => {
                const id = circleIdSelect ? String(circleIdSelect.value || '') : '';
                renderConnectionPick(circlePick, allCircleConnections, circleMemberIds?.[id] || []);
            };

            familyIdSelect?.addEventListener('change', () => updateFamilyPick());
            circleIdSelect?.addEventListener('change', () => updateCirclePick());

            updateFamilyPick();
            updateCirclePick();

            bindConnectionPicker({ pickId: 'familyInviteConnectionPick', useId: 'familyInviteUseConnection', formId: 'familyInviteForm' });
            bindConnectionPicker({ pickId: 'circleInviteConnectionPick', useId: 'circleInviteUseConnection', formId: 'circleInviteForm' });

            const hideStepsToggle = document.getElementById('alHideSteps');
            hideStepsToggle?.addEventListener('change', async () => {
                const hide = Boolean(hideStepsToggle.checked);
                const share = !hide;

                try {
                    await fetch('{{ route('me.share_location') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ share_location: share }),
                    });
                } catch {
                }

                window.location.reload();
            });

            const alertsBadge = document.getElementById('alAlertsBadge');
            const alertsList = document.getElementById('alAlertsList');
            const alertsSubtitle = document.getElementById('alAlertsSubtitle');
            const alertsMarkAll = document.getElementById('alAlertsMarkAll');
            let lastUnseen = 0;

            const playAlertSound = () => {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const o = ctx.createOscillator();
                    const g = ctx.createGain();
                    o.type = 'sine';
                    o.frequency.value = 880;
                    g.gain.value = 0.0001;
                    o.connect(g);
                    g.connect(ctx.destination);
                    o.start();
                    g.gain.exponentialRampToValueAtTime(0.06, ctx.currentTime + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.18);
                    o.stop(ctx.currentTime + 0.2);
                    window.setTimeout(() => ctx.close().catch(() => {}), 240);
                } catch {
                }
            };

            const setBadge = (count) => {
                const n = Number(count || 0);
                if (!alertsBadge) return;
                if (n > 0) {
                    alertsBadge.textContent = String(n);
                    alertsBadge.classList.remove('d-none');
                } else {
                    alertsBadge.textContent = '0';
                    alertsBadge.classList.add('d-none');
                }
            };

            const renderAlerts = (data) => {
                const list = Array.isArray(data?.alerts) ? data.alerts : [];
                const unseen = Number(data?.unseen_count || 0);

                if (alertsSubtitle) {
                    alertsSubtitle.textContent = unseen > 0 ? `${unseen} não visualizada(s)` : 'Nenhuma notificação pendente';
                }
                setBadge(unseen);

                if (!alertsList) return;
                alertsList.innerHTML = '';

                if (!list.length) {
                    const empty = document.createElement('div');
                    empty.className = 'text-secondary';
                    empty.textContent = 'Nenhuma notificação ainda.';
                    alertsList.appendChild(empty);
                    return;
                }

                list.forEach((a) => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item bg-transparent text-white border-secondary border-opacity-25';

                    const avatar = document.createElement('div');
                    avatar.className = 'al-profile-circle';
                    avatar.style.width = '44px';
                    avatar.style.height = '44px';
                    avatar.style.flex = '0 0 44px';

                    const photo = a?.actor_photo ? String(a.actor_photo) : '';
                    const initials = String(a?.actor_initials || '').slice(0, 2).toUpperCase();
                    if (photo) {
                        avatar.innerHTML = `<img src="${photo}" alt="" class="al-profile-circle__img">`;
                    } else {
                        avatar.innerHTML = `<span class="al-profile-circle__initials">${initials}</span>`;
                    }

                    const title = document.createElement('div');
                    title.className = 'fw-semibold';
                    title.textContent = String(a?.actor_name || 'Usuário');

                    const msg = document.createElement('div');
                    msg.className = 'text-secondary small';
                    msg.textContent = String(a?.message || '');

                    const date = document.createElement('div');
                    date.className = 'text-secondary small';
                    date.textContent = String(a?.date || '');

                    const meta = document.createElement('div');
                    meta.className = 'd-flex align-items-start justify-content-between gap-3';

                    const left = document.createElement('div');
                    left.className = 'd-flex align-items-center gap-3';
                    left.appendChild(avatar);

                    const text = document.createElement('div');
                    text.appendChild(title);
                    text.appendChild(msg);
                    left.appendChild(text);

                    const right = document.createElement('div');
                    right.className = 'text-end';
                    right.appendChild(date);

                    if (!a?.seen) {
                        const dot = document.createElement('div');
                        dot.style.width = '8px';
                        dot.style.height = '8px';
                        dot.style.borderRadius = '999px';
                        dot.style.background = '#ff3b30';
                        dot.style.marginLeft = 'auto';
                        dot.style.marginTop = '6px';
                        right.appendChild(dot);
                    }

                    meta.appendChild(left);
                    meta.appendChild(right);
                    item.appendChild(meta);
                    alertsList.appendChild(item);
                });
            };

            const fetchAlerts = async ({ silentSound = false } = {}) => {
                try {
                    const res = await fetch('{{ route('alerts.index') }}', {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) return;

                    const unseen = Number(data?.unseen_count || 0);
                    if (!silentSound && unseen > lastUnseen) {
                        playAlertSound();
                    }
                    lastUnseen = unseen;

                    renderAlerts(data);
                } catch {
                }
            };

            alertsMarkAll?.addEventListener('click', async () => {
                try {
                    const res = await fetch('{{ route('alerts.mark_all_seen') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({}),
                    });
                    if (!res.ok) return;
                    lastUnseen = 0;
                    await fetchAlerts({ silentSound: true });
                } catch {
                }
            });

            document.getElementById('alertsModal')?.addEventListener('shown.bs.modal', () => {
                fetchAlerts({ silentSound: true });
            });

            fetchAlerts({ silentSound: true });
            window.setInterval(() => fetchAlerts(), 5000);

            const setButtonLoading = (btn, loading) => {
                if (!btn) return;

                if (loading) {
                    if (!btn.dataset.label) btn.dataset.label = btn.textContent || '';
                    btn.setAttribute('disabled', 'disabled');
                    btn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span><span>Aguarde...</span>';
                } else {
                    btn.removeAttribute('disabled');
                    const label = btn.dataset.label || 'OK';
                    btn.textContent = label;
                }
            };

            const handleJsonForm = (formId, endpoint, onSuccess) => {
                const f = document.getElementById(formId);
                if (!f) return;

                f.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const submit = f.querySelector('button[type="submit"]');
                    setButtonLoading(submit, true);

                    try {
                        const fd = new FormData(f);
                        const payload = {};
                        fd.forEach((v, k) => {
                            payload[k] = v;
                        });

                        const res = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Content-Type': 'application/json',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw data;

                        onSuccess?.(data, f);
                    } catch {
                    } finally {
                        setButtonLoading(submit, false);
                    }
                });
            };

            handleJsonForm('familiesCreateForm', '{{ route('families.store') }}', (data, f) => {
                const family = data?.family;
                if (!family) return;

                const input = f.querySelector('input[name="name"]');
                if (input) input.value = '';

                const modal = document.getElementById('familiesModal');
                const body = modal?.querySelector('.modal-body');
                if (!body) return;

                let list = body.querySelector('.list-group');
                if (!list) {
                    const empty = body.querySelector('.text-secondary');
                    if (empty) empty.remove();
                    list = document.createElement('div');
                    list.className = 'list-group list-group-flush';
                    body.appendChild(list);
                }

                const item = document.createElement('div');
                item.className = 'list-group-item bg-transparent text-white border-secondary border-opacity-25';
                item.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold"></div>
                            <div class="text-secondary small"></div>
                        </div>
                        <button class="al-btn-secondary" type="button">Abrir</button>
                    </div>
                `;
                item.querySelector('.fw-semibold').textContent = String(family.name || '');
                item.querySelector('.text-secondary').textContent = `${Number(family.users_count || 1)} pessoa(s)`;
                list.prepend(item);
                familyInvite?.addOption?.({ id: family.id, name: family.name });
            });

            handleJsonForm('circlesCreateForm', '{{ route('circles.store') }}', (data, f) => {
                const circle = data?.circle;
                if (!circle) return;

                const input = f.querySelector('input[name="name"]');
                if (input) input.value = '';

                const modal = document.getElementById('circlesModal');
                const body = modal?.querySelector('.modal-body');
                if (!body) return;

                let list = body.querySelector('.list-group');
                if (!list) {
                    const empty = body.querySelector('.text-secondary');
                    if (empty) empty.remove();
                    list = document.createElement('div');
                    list.className = 'list-group list-group-flush';
                    body.appendChild(list);
                }

                const item = document.createElement('div');
                item.className = 'list-group-item bg-transparent text-white border-secondary border-opacity-25';
                item.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold"></div>
                            <div class="text-secondary small"></div>
                        </div>
                        <button class="al-btn-secondary" type="button">Abrir</button>
                    </div>
                `;
                item.querySelector('.fw-semibold').textContent = String(circle.name || '');
                item.querySelector('.text-secondary').textContent = `${Number(circle.users_count || 1)} pessoa(s)`;
                list.prepend(item);
                circleInvite?.addOption?.({ id: circle.id, name: circle.name });
            });

            const safePlaceForm = document.getElementById('safePlaceForm');
            safePlaceForm?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const submit = document.getElementById('safePlaceSubmit');
                const hint = document.getElementById('safePlaceHint');
                setButtonLoading(submit, true);

                try {
                    const fd = new FormData(safePlaceForm);
                    const payload = {};
                    fd.forEach((v, k) => {
                        payload[k] = v;
                    });

                    const res = await fetch('{{ route('safe_places.store') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });

                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw data;

                    const place = data?.safe_place;
                    if (!place) return;

                    safePlaceForm.querySelector('input[name="name"]').value = '';
                    safePlaceForm.querySelector('input[name="address"]').value = '';
                    safePlaceForm.querySelector('#safePlaceLat').value = '';
                    safePlaceForm.querySelector('#safePlaceLng').value = '';
                    safePlaceForm.querySelector('#safePlaceRadius').value = '150';
                    safePlaceForm.querySelector('#safePlaceRadiusValue').textContent = '150';

                    const modal = document.getElementById('safePlacesModal');
                    const body = modal?.querySelector('.modal-body');
                    if (body) {
                        let list = body.querySelector('.list-group');
                        if (!list) {
                            const empty = body.querySelector('.text-secondary');
                            if (empty) empty.remove();
                            list = document.createElement('div');
                            list.className = 'list-group list-group-flush';
                            body.appendChild(list);
                        }

                        const item = document.createElement('div');
                        item.className = 'list-group-item bg-transparent text-white border-secondary border-opacity-25';
                        item.innerHTML = `
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold"></div>
                                    <div class="text-secondary small"></div>
                                </div>
                                <button class="al-btn-secondary" type="button">Ver</button>
                            </div>
                        `;
                        item.querySelector('.fw-semibold').textContent = String(place.name || '');
                        item.querySelector('.text-secondary').textContent = `Raio: ${Number(place.radius || 0)}m`;
                        list.prepend(item);
                    }

                    window.AirlinkMap?.refreshSafePlaces?.();
                    window.AirlinkMap?.clearSafePlaceDraft?.();
                } catch {
                    if (hint) {
                        hint.textContent = 'Aguarde e confira: selecione um endereço válido para definir o local no mapa.';
                    }
                } finally {
                    setButtonLoading(submit, false);
                }
            });

            const mePhotoForm = document.getElementById('mePhotoForm');
            const mePhotoSubmit = document.getElementById('mePhotoSubmit');
            mePhotoForm?.addEventListener('submit', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                setButtonLoading(mePhotoSubmit, true);

                try {
                    const fd = new FormData(mePhotoForm);
                    const res = await fetch('{{ route('me.photo') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        credentials: 'same-origin',
                        body: fd,
                    });

                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw data;

                    const url = String(data?.url || '');
                    if (url) {
                        const preview = document.getElementById('mePhotoPreview');
                        if (preview) {
                            preview.innerHTML = '';
                            const img = document.createElement('img');
                            img.src = url;
                            img.alt = 'Foto de perfil';
                            img.className = 'al-profile-circle__img';
                            preview.appendChild(img);
                        }

                        const navImg = document.getElementById('alNavbarAvatarImg');
                        const navInitials = document.getElementById('alNavbarAvatarInitials');
                        if (navImg) {
                            navImg.src = url;
                            navImg.classList.remove('d-none');
                        }
                        navInitials?.classList.add('d-none');
                    }
                } catch {
                } finally {
                    setButtonLoading(mePhotoSubmit, false);
                }
            }, true);

            const input = document.getElementById('mePhotoInput');
            const dropzone = document.getElementById('mePhotoDropzone');
            const preview = document.getElementById('mePhotoPreview');

            const readFile = (file) => {
                if (!file || !preview) return;
                const url = URL.createObjectURL(file);
                preview.innerHTML = '';
                const img = document.createElement('img');
                img.src = url;
                img.alt = 'Foto de perfil';
                img.className = 'al-profile-circle__img';
                preview.appendChild(img);
            };

            input?.addEventListener('change', () => {
                const file = input.files && input.files[0] ? input.files[0] : null;
                if (file) readFile(file);
            });

            const setOver = (isOver) => {
                if (!dropzone) return;
                dropzone.classList.toggle('al-dropzone--over', isOver);
            };

            dropzone?.addEventListener('dragover', (e) => {
                e.preventDefault();
                setOver(true);
            });
            dropzone?.addEventListener('dragleave', () => setOver(false));
            dropzone?.addEventListener('drop', (e) => {
                e.preventDefault();
                setOver(false);
                const file = e.dataTransfer?.files?.[0];
                if (!file || !input) return;
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                readFile(file);
            });
        })();
    </script>

    @php
        $needsOnboarding = auth()->check() && ! (bool) auth()->user()->airlink_locate_fisrt_entire;
    @endphp

    @if ($needsOnboarding)
        <div class="modal fade" id="legalModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content al-card">
                    <div class="modal-body p-4 p-md-5">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="{{ asset('airlink-locate-logo-white.png') }}" alt="Airlink Locate" style="height: 26px; width: auto;">
                            <div class="vr opacity-25"></div>
                            <img src="{{ asset('SacratechID-Icon.png') }}" alt="Sacratech iD" style="height: 30px; width: auto;">
                        </div>

                        <div class="al-onboarding-step al-auth-card" data-step="1">
                            <div class="h3 fw-semibold mb-2">Termos de Uso</div>
                            <div class="text-secondary mb-3">
                                Leia com atenção. Para usar o Airlink Locate, é necessário aceitar os Termos.
                            </div>

                            <div class="al-legal-scroll al-card al-card-strong p-3 p-md-4 mb-3">
                                <div class="text-secondary small">
                                    Estes Termos regem o acesso e uso do Airlink Locate (“Serviço”), disponibilizado pela Sacratech Softwares (“Sacratech”).
                                    Ao utilizar o Serviço, você declara que leu e concorda com as regras abaixo.

                                    <div class="mt-3 fw-semibold text-white">1. Finalidade do Serviço</div>
                                    O Serviço permite compartilhar e visualizar localização em tempo real e histórico recente entre pessoas que aceitam convites
                                    (famílias, círculos e conexões diretas), com foco em uso legítimo, consentido e seguro.

                                    <div class="mt-3 fw-semibold text-white">2. Consentimento e uso responsável</div>
                                    Você se compromete a obter autorização válida antes de compartilhar/visualizar localização de terceiros e a respeitar
                                    recusas, revogações e saídas de grupos. É proibido usar o Serviço para perseguição, vigilância não autorizada, assédio,
                                    ameaça, stalking, invasão de privacidade ou qualquer finalidade ilícita.

                                    <div class="mt-3 fw-semibold text-white">3. Conta, autenticação e segurança</div>
                                    O acesso pode exigir Sacratech iD. Você é responsável por proteger credenciais, manter informações atualizadas, ativar 2FA
                                    quando disponível e comunicar suspeitas de acesso indevido. Podemos aplicar controles de segurança (rate limiting,
                                    detecção de abuso, bloqueios e auditoria).

                                    <div class="mt-3 fw-semibold text-white">4. Convites e compartilhamento</div>
                                    O compartilhamento ocorre por convites. Quem recebe pode aceitar ou recusar. Convites podem expirar ou ser revogados.
                                    O Usuário pode encerrar compartilhamentos ao sair de grupos, remover conexões ou revogar permissões do dispositivo.

                                    <div class="mt-3 fw-semibold text-white">5. Localização e permissões</div>
                                    O Serviço depende de localização e permissões do dispositivo. Você pode negar, porém isso limita ou impede recursos
                                    essenciais (mapa e atualizações). O funcionamento também depende de conectividade e configurações do sistema.

                                    <div class="mt-3 fw-semibold text-white">6. Propriedade intelectual</div>
                                    O Serviço, marca, interface, APIs e componentes são protegidos por propriedade intelectual. É proibida engenharia reversa,
                                    exploração de vulnerabilidades, abuso de APIs, scraping e acesso não autorizado.

                                    <div class="mt-3 fw-semibold text-white">7. Disponibilidade e limitações</div>
                                    O Serviço pode sofrer manutenções, interrupções e limitações por fatores externos. O Serviço é fornecido “como está”, com
                                    esforços razoáveis de estabilidade e proteção.

                                    <div class="mt-3 fw-semibold text-white">8. Privacidade</div>
                                    O tratamento de dados é regido também pela Política de Privacidade. A Sacratech declara que não utiliza nem divulga
                                    localizações para fins lucrativos, venda de dados ou publicidade comportamental.

                                    <div class="mt-3 fw-semibold text-white">9. Medidas contra abuso</div>
                                    Podemos suspender ou encerrar acesso em caso de violação destes Termos, suspeita de abuso, fraude, risco a terceiros ou
                                    exigência legal.

                                    <div class="mt-3 fw-semibold text-white">10. Alterações</div>
                                    Podemos atualizar estes Termos. O uso continuado após alterações indica concordância com a versão vigente.

                                    <div class="mt-3">
                                        Versão completa em
                                        <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Termos de Uso</a>.
                                    </div>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="acceptTerms">
                                <label class="form-check-label" for="acceptTerms">Li e aceito os Termos de Uso</label>
                            </div>

                            <button class="w-100 al-btn-primary" type="button" id="goPrivacy" disabled>
                                Continuar
                            </button>
                        </div>

                        <div class="al-onboarding-step al-auth-card d-none" data-step="2">
                            <div class="h3 fw-semibold mb-2">Política de Privacidade</div>
                            <div class="text-secondary mb-3">
                                Leia com atenção. Para usar o Airlink Locate, é necessário aceitar a Política.
                            </div>

                            <div class="al-legal-scroll al-card al-card-strong p-3 p-md-4 mb-3">
                                <div class="text-secondary small">
                                    Esta Política descreve como tratamos dados no Airlink Locate. O Serviço lida com dados de localização, portanto aplicamos
                                    princípios de minimização, finalidade, segurança e controle pelo Usuário.

                                    <div class="mt-3 fw-semibold text-white">1. Dados tratados</div>
                                    Podemos tratar dados de conta (nome, email, identificadores), dados técnicos (dispositivo, sessão, IP, logs), dados de uso
                                    (convites, aceites, recusas) e dados de localização (latitude/longitude e horários) necessários para os recursos.

                                    <div class="mt-3 fw-semibold text-white">2. Finalidades</div>
                                    Autenticar, fornecer mapa e atualizações, viabilizar compartilhamento entre conexões aceitas, prevenir abuso, melhorar
                                    estabilidade e cumprir obrigações legais.

                                    <div class="mt-3 fw-semibold text-white">3. Compartilhamento por consentimento</div>
                                    Localizações são compartilhadas somente quando há convite aceito e enquanto a relação existir. Você pode encerrar a qualquer
                                    momento removendo conexões ou revogando permissões do dispositivo.

                                    <div class="mt-3 fw-semibold text-white">4. Não comercialização</div>
                                    A Sacratech não utiliza nem divulga localizações para fins lucrativos, venda de dados, publicidade comportamental ou
                                    monetização baseada em localização.

                                    <div class="mt-3 fw-semibold text-white">5. Terceiros e infraestrutura</div>
                                    Podemos utilizar provedores de infraestrutura sob confidencialidade e segurança, compartilhando apenas dados mínimos para
                                    operação. Também poderemos divulgar informações por exigência legal, ordem judicial ou para proteger direitos e segurança.

                                    <div class="mt-3 fw-semibold text-white">6. Retenção e segurança</div>
                                    Retemos dados pelo tempo necessário às finalidades e segurança. Adotamos controles de acesso, autenticação, proteção contra
                                    abuso e medidas de integridade. Ainda assim, nenhum sistema é absolutamente imune.

                                    <div class="mt-3">
                                        Versão completa em
                                        <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer">Política de Privacidade</a>.
                                    </div>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="acceptPrivacy">
                                <label class="form-check-label" for="acceptPrivacy">Li e aceito a Política de Privacidade</label>
                            </div>

                            <button class="w-100 al-btn-primary" type="button" id="finishLegal" disabled>
                                Continuar
                            </button>
                            <button class="w-100 al-btn-secondary mt-2" type="button" id="backTerms">
                                Voltar
                            </button>
                        </div>

                        <div class="al-onboarding-step al-auth-card d-none" data-step="3">
                            <div class="h3 fw-semibold mb-2">Localização</div>
                            <div class="text-secondary mb-3">
                                Para o Airlink Locate funcionar, é recomendado permitir acesso à sua localização.
                            </div>

                            <div class="al-card al-card-strong p-3 p-md-4 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="mdi mdi-crosshairs-gps fs-3"></i>
                                    <div>
                                        <div class="fw-semibold text-white">Compartilhamento de localização</div>
                                        <div class="text-secondary small mt-1">
                                            Sem localização, mapa, atualizações e alertas ficam limitados. Você pode recusar, mas verá um aviso e recursos
                                            essenciais não funcionarão.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button class="al-btn-primary flex-grow-1" type="button" id="allowLocation">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" id="locSpinner" aria-hidden="true"></span>
                                    Permitir localização
                                </button>
                                <a class="al-btn-secondary flex-grow-1 text-decoration-none text-center" href="{{ route('location.required') }}">
                                    Continuar sem localização
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (() => {
                const modalEl = document.getElementById('legalModal');
                if (!modalEl || typeof bootstrap === 'undefined') return;

                const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false, focus: true });
                modal.show();

                const stepEls = [...modalEl.querySelectorAll('.al-onboarding-step')];
                const showStep = (n) => {
                    stepEls.forEach((el) => {
                        const isActive = el.getAttribute('data-step') === String(n);
                        el.classList.toggle('d-none', !isActive);
                        if (isActive) {
                            el.classList.remove('al-auth-card--leave');
                            requestAnimationFrame(() => el.classList.add('al-auth-card--enter'));
                        } else {
                            el.classList.remove('al-auth-card--enter');
                        }
                    });
                };
                const transitionTo = (next) => {
                    const current = stepEls.find((el) => !el.classList.contains('d-none'));
                    if (!current) {
                        showStep(next);
                        return;
                    }

                    current.classList.add('al-auth-card--leave');

                    window.setTimeout(() => {
                        current.classList.add('d-none');
                        showStep(next);
                    }, 180);
                };
                showStep(1);

                const acceptTerms = document.getElementById('acceptTerms');
                const acceptPrivacy = document.getElementById('acceptPrivacy');
                const goPrivacy = document.getElementById('goPrivacy');
                const finishLegal = document.getElementById('finishLegal');
                const backTerms = document.getElementById('backTerms');
                const allowLocation = document.getElementById('allowLocation');
                const locSpinner = document.getElementById('locSpinner');

                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                if (acceptTerms && goPrivacy) {
                    acceptTerms.addEventListener('change', () => {
                        goPrivacy.toggleAttribute('disabled', !acceptTerms.checked);
                    });
                }

                if (acceptPrivacy && finishLegal) {
                    acceptPrivacy.addEventListener('change', () => {
                        finishLegal.toggleAttribute('disabled', !acceptPrivacy.checked);
                    });
                }

                goPrivacy?.addEventListener('click', () => {
                    transitionTo(2);
                });

                backTerms?.addEventListener('click', () => {
                    transitionTo(1);
                });

                finishLegal?.addEventListener('click', async () => {
                    finishLegal.setAttribute('disabled', 'disabled');
                    try {
                        const res = await fetch('{{ route('legal.accept') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({}),
                        });
                        if (!res.ok) throw new Error('request_failed');
                        transitionTo(3);
                    } catch (e) {
                        window.location.reload();
                    }
                });

                allowLocation?.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        window.location.href = '{{ route('location.required') }}';
                        return;
                    }
                    allowLocation.setAttribute('disabled', 'disabled');
                    if (locSpinner) locSpinner.classList.remove('d-none');

                    navigator.geolocation.getCurrentPosition(
                        () => {
                            localStorage.setItem('airlink_location_allowed', '1');
                            modal.hide();
                        },
                        () => {
                            localStorage.setItem('airlink_location_allowed', '0');
                            window.location.href = '{{ route('location.required') }}';
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                });
            })();
        </script>
    @endif
@endsection
