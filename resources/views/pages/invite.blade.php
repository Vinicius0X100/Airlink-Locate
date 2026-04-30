@extends('layouts.site')

@section('title', 'Convite - Airlink Locate')

@section('content')
    <section class="position-relative overflow-hidden">
        <div class="al-hero-glow"></div>
        <div class="al-hero-glow-2"></div>

        <div class="container py-5 py-md-6 position-relative">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="al-card p-4 p-md-5">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="{{ asset('airlink-locate-logo-white.png') }}" alt="Airlink Locate" style="height: 28px; width: auto;">
                            <div class="vr opacity-25"></div>
                            <img src="{{ asset('SacratechID-Icon.png') }}" alt="Sacratech iD" style="height: 32px; width: auto;">
                        </div>

                        <div class="h3 fw-semibold mb-2">Convite</div>

                        @if (!empty($mismatch))
                            <div class="text-secondary mb-3">
                                {{ $mismatch_reason ?? 'Este convite não pode ser aceito com a conta atual.' }}
                            </div>
                            <div class="al-card al-card-strong p-3 mb-3">
                                @if (!empty($expected_email))
                                    <div class="text-secondary small">Email do convite</div>
                                    <div class="text-white fw-semibold">{{ $expected_email }}</div>
                                @endif
                                @if (!empty($current_email))
                                    <div class="text-secondary small mt-2">Você está logado como</div>
                                    <div class="text-white fw-semibold">{{ $current_email }}</div>
                                @endif
                            </div>
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <form method="POST" action="{{ route('logout') }}" class="flex-grow-1">
                                    @csrf
                                    <button type="submit" class="w-100 al-btn-primary">Sair e trocar conta</button>
                                </form>
                                <a href="{{ route('dashboard') }}" class="flex-grow-1 al-btn-secondary text-decoration-none d-inline-flex align-items-center justify-content-center">
                                    Voltar ao painel
                                </a>
                            </div>
                        @else
                        @php
                            $isExpired = $invitation->expires_at ? $invitation->expires_at->isPast() : false;
                        @endphp

                        @if ($invitation->status !== 'pending')
                            <div class="text-secondary">
                                Este convite já foi processado.
                            </div>
                        @elseif ($isExpired)
                            <div class="text-secondary">
                                Este convite expirou.
                            </div>
                        @else
                            <div class="text-secondary mb-4">
                                <span class="text-white fw-semibold">{{ $invitation->inviter?->name ?? 'Um usuário' }}</span>
                                @if ($invitation->type === 'family')
                                    convidou você para a família
                                    <span class="text-white fw-semibold">{{ $invitation->family?->name ?? '' }}</span>.
                                @elseif ($invitation->type === 'circle')
                                    convidou você para o círculo
                                    <span class="text-white fw-semibold">{{ $invitation->circle?->name ?? '' }}</span>.
                                @else
                                    convidou você para compartilhar localização no Airlink Locate.
                                @endif
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <form method="POST" action="{{ route('invite.accept', ['token' => $token]) }}" class="flex-grow-1">
                                    @csrf
                                    <button type="submit" class="w-100 al-btn-primary">Aceitar</button>
                                </form>
                                <form method="POST" action="{{ route('invite.decline', ['token' => $token]) }}" class="flex-grow-1">
                                    @csrf
                                    <button type="submit" class="w-100 al-btn-secondary">Recusar</button>
                                </form>
                            </div>

                            @if ($invitation->expires_at)
                                <div class="small text-secondary mt-3">
                                    Expira em {{ $invitation->expires_at->format('d/m/Y H:i') }}.
                                </div>
                            @endif
                        @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

