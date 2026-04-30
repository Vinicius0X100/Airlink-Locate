@extends('layouts.site')

@section('title', 'Sobre - Airlink Locate')

@section('meta_description', 'Conheça o Airlink Locate: localização em tempo real para acompanhar família e amigos, com privacidade, avisos úteis e uma experiência simples para o dia a dia.')

@section('content')
    <section class="position-relative overflow-hidden">
        <div class="al-hero-glow"></div>
        <div class="al-hero-glow-2"></div>

        <div class="container position-relative py-5 py-md-6">
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="al-pill d-inline-flex align-items-center gap-2 px-3 py-2">
                        <i class="mdi mdi-information-outline fs-5"></i>
                        <span class="small text-secondary">Sobre o Airlink Locate</span>
                    </div>

                    <h1 class="mt-4 display-5 fw-semibold">Uma forma simples de acompanhar quem importa.</h1>

                    <p class="mt-3 lead text-secondary">
                        O Airlink Locate ajuda você a saber onde sua família e seus amigos estão, de forma clara e sem complicação. Em vez de ficar
                        perguntando o tempo todo, você acompanha no mapa e recebe avisos quando algo muda.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="al-section">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-5">
                    <div class="al-icon">
                        <i class="mdi mdi-map-outline"></i>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="h2 fw-semibold mb-3">Mais tranquilidade</div>
                    <div class="text-secondary fs-5">
                        Saiba quando alguém chegou bem, ou quando está a caminho, sem depender de mensagens o tempo todo.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="al-section al-section-alt">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-7 order-2 order-lg-1">
                    <div class="h2 fw-semibold mb-3">Feito para pessoas</div>
                    <div class="text-secondary fs-5">
                        Linguagem simples, telas diretas e uma experiência “Apple-like”: limpa, elegante e fácil de usar.
                    </div>
                </div>
                <div class="col-12 col-lg-5 order-1 order-lg-2">
                    <div class="al-icon">
                        <i class="mdi mdi-account-heart-outline"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="al-section">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-5">
                    <div class="al-icon">
                        <i class="mdi mdi-access-point"></i>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="h2 fw-semibold mb-3">Em tempo real, quando faz sentido</div>
                    <div class="text-secondary fs-5">
                        Atualizações ao vivo e “último visto” para dar contexto. Você entende o que mudou, sem excesso de informação.
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
