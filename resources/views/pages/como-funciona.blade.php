@extends('layouts.site')

@section('title', 'Como funciona - Airlink Locate')

@section('content')
    <div class="container py-5">
        <div class="row">
            <div class="col-12 col-lg-8">
                <h1 class="display-6 fw-semibold">Como funciona</h1>
                <p class="mt-3 text-white opacity-75">
                    O Airlink Locate foi feito para ser simples: você entra, escolhe com quem quer se conectar e acompanha tudo pelo mapa.
                    Sem termos técnicos e sem complicação.
                </p>

                <div class="mt-4 d-grid gap-3">
                    <div class="al-card p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="al-step-badge">
                                1
                            </div>
                            <div class="fw-semibold">Entre na sua conta</div>
                        </div>
                        <div class="mt-2 small text-white opacity-75">
                            Acesse com suas credenciais. Se a verificação em duas etapas estiver ativa, basta informar o código.
                        </div>
                    </div>

                    <div class="al-card p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="al-step-badge">
                                2
                            </div>
                            <div class="fw-semibold">Crie sua família e seus círculos</div>
                        </div>
                        <div class="mt-2 small text-white opacity-75">
                            Organize seus contatos em grupos. Assim fica fácil saber quem você está acompanhando.
                        </div>
                    </div>

                    <div class="al-card p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="al-step-badge">
                                3
                            </div>
                            <div class="fw-semibold">Acompanhe no mapa e receba avisos</div>
                        </div>
                        <div class="mt-2 small text-white opacity-75">
                            Veja posições no mapa e crie locais seguros como “Casa” e “Trabalho” para receber alertas de chegada e saída.
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-12 col-md-4">
                        <div class="al-card p-4 h-100">
                            <i class="mdi mdi-map-marker-check-outline fs-4"></i>
                            <div class="mt-3 fw-semibold">Locais seguros</div>
                            <div class="mt-2 small text-white opacity-75">
                                Configure um raio e receba avisos automáticos.
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="al-card p-4 h-100">
                            <i class="mdi mdi-wifi fs-4"></i>
                            <div class="mt-3 fw-semibold">Ao vivo</div>
                            <div class="mt-2 small text-white opacity-75">
                                Atualizações em tempo real quando necessário.
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="al-card p-4 h-100">
                            <i class="mdi mdi-eye-outline fs-4"></i>
                            <div class="mt-3 fw-semibold">Visibilidade clara</div>
                            <div class="mt-2 small text-white opacity-75">
                                Veja quem está online e o último horário de atualização.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
