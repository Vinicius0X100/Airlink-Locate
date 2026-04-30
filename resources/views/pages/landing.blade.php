@extends('layouts.site')

@section('title', 'Airlink Locate')

@section('meta_description', 'Rastreie localização em tempo real e acompanhe família, amigos e itens com privacidade. Ideal para saber onde estão, receber avisos e até ajudar a encontrar celular perdido.')

@section('seo')
    <meta name="robots" content="index,follow">
    <meta name="keywords"
        content="rastrear pessoas, acompanhamento de pessoas, localização em tempo real, rastrear família, rastrear amigos, rastrear celular, encontrar meu celular, localizar telefone, rastrear itens, rastreador, alternativa find my, find my iphone, localização ao vivo">
    @php
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Airlink Locate',
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $appSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Airlink Locate',
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'Web',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'BRL',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Sacratech Softwares LTDA',
            ],
            'description' => 'Localização em tempo real para acompanhar família e amigos com privacidade, receber avisos e organizar círculos.',
        ];
    @endphp

    <script type="application/ld+json">{{ json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</script>
    <script type="application/ld+json">{{ json_encode($appSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</script>
@endsection

@section('content')
    <section class="al-hero position-relative overflow-hidden">
        <div class="al-hero-video">
            <video class="al-hero-video__media" autoplay muted loop playsinline preload="metadata">
                <source src="{{ asset('media/hero.mp4') }}" type="video/mp4">
            </video>
        </div>
        <div class="al-hero-overlay"></div>

        <div class="container py-5 py-md-6 al-hero-content">
            <div class="row">
                <div class="col-12 col-lg-7">
                    <div class="al-pill d-inline-flex align-items-center gap-2 px-3 py-2">
                        <i class="mdi mdi-access-point fs-5"></i>
                        <span class="small text-secondary">Localização em tempo real, de um jeito simples</span>
                    </div>

                    <h1 class="mt-4 display-5 fw-semibold">
                        Saiba onde sua família e seus amigos estão, sem complicação.
                    </h1>

                    <p class="mt-3 lead text-secondary">
                        Veja quem está no caminho, quem já chegou e o que mudou — com uma experiência limpa, bonita e fácil de entender.
                    </p>

                    <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                        <a href="{{ route('login') }}" class="al-btn-primary text-decoration-none">
                            Entrar
                        </a>
                        <a href="#mapa" class="al-btn-secondary text-decoration-none">
                            Saiba mais
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="mapa" class="al-section">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-5">
                    <div class="al-icon">
                        <i class="mdi mdi-map-marker-radius"></i>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="h2 fw-semibold mb-3">Mapa ao vivo</div>
                    <div class="text-secondary fs-5">
                        Acompanhe em tempo real e encontre quem você precisa com rapidez, com visual claro e sem excesso de informação.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="avisos" class="al-section al-section-alt">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-7 order-2 order-lg-1">
                    <div class="h2 fw-semibold mb-3">Avisos úteis</div>
                    <div class="text-secondary fs-5">
                        Receba alertas do que importa: quando alguém chega ou sai, ou quando houver uma mudança que mereça atenção.
                    </div>
                </div>
                <div class="col-12 col-lg-5 order-1 order-lg-2">
                    <div class="al-icon">
                        <i class="mdi mdi-bell-ring-outline"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="familia" class="al-section">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-5">
                    <div class="al-icon">
                        <i class="mdi mdi-account-group-outline"></i>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="h2 fw-semibold mb-3">Família e círculos</div>
                    <div class="text-secondary fs-5">
                        Organize as pessoas em grupos do seu jeito. Assim você acompanha com praticidade, sem bagunça.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="privacidade" class="al-section al-section-alt">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-7 order-2 order-lg-1">
                    <div class="h2 fw-semibold mb-3">Privacidade por padrão</div>
                    <div class="text-secondary fs-5">
                        Você escolhe com quem compartilhar. Tudo foi desenhado para ser transparente, simples e seguro.
                    </div>
                </div>
                <div class="col-12 col-lg-5 order-1 order-lg-2">
                    <div class="al-icon">
                        <i class="mdi mdi-shield-check-outline"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="tempo-real" class="al-section">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-5">
                    <div class="al-icon">
                        <i class="mdi mdi-clock-outline"></i>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="h2 fw-semibold mb-3">Atualizações em tempo real</div>
                    <div class="text-secondary fs-5">
                        Acompanhe mudanças assim que acontecem e tenha uma visão rápida de quando foi a última atualização.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="apps" class="al-section al-section-alt">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-7 order-2 order-lg-1">
                    <div class="h2 fw-semibold mb-3">Use em qualquer lugar</div>
                    <div class="text-secondary fs-5">
                        Baixe no Android e iOS, e também use no seu computador — Windows (Microsoft Store), macOS e outros. O ecossistema Airlink foi
                        feito para você acompanhar de onde quiser, quando quiser.
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <div class="al-pill d-inline-flex align-items-center gap-2 px-3 py-2">
                            <i class="mdi mdi-android fs-5"></i>
                            <span class="small text-secondary">Android</span>
                        </div>
                        <div class="al-pill d-inline-flex align-items-center gap-2 px-3 py-2">
                            <i class="mdi mdi-apple fs-5"></i>
                            <span class="small text-secondary">iOS</span>
                        </div>
                        <div class="al-pill d-inline-flex align-items-center gap-2 px-3 py-2">
                            <i class="mdi mdi-microsoft-windows fs-5"></i>
                            <span class="small text-secondary">Windows Store</span>
                        </div>
                        <div class="al-pill d-inline-flex align-items-center gap-2 px-3 py-2">
                            <i class="mdi mdi-laptop fs-5"></i>
                            <span class="small text-secondary">macOS e mais</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5 order-1 order-lg-2">
                    <div class="al-icon">
                        <i class="mdi mdi-devices fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="sacratech-id" class="al-section al-section-alt">
        <div class="container py-5 py-md-6">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-5 order-1 order-lg-2">
                    <div class="al-icon">
                        <img src="{{ asset('SacratechID-Icon.png') }}" alt="Sacratech iD" style="max-height: 64px; max-width: 64px; width: auto; height: auto;">
                    </div>
                </div>
                <div class="col-12 col-lg-7 order-2 order-lg-1">
                    <div class="h2 fw-semibold mb-3">Conta Sacratech iD</div>
                    <div class="text-secondary fs-5">
                        Para usar o Airlink Locate, você precisa de uma conta Sacratech iD. Criar sua conta é totalmente gratuito e é por ela que o
                        sistema autentica seus acessos.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="al-section">
        <div class="container py-5 py-md-6">
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="h2 fw-semibold mb-3">Perguntas frequentes</div>
                    <div class="text-secondary fs-5 mb-4">
                        Localização, rastreamento e acompanhamento — de um jeito claro e feito para o dia a dia.
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-lg-10">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item al-card">
                            <h3 class="accordion-header" id="faq1h">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1"
                                    aria-expanded="false" aria-controls="faq1">
                                    Dá para rastrear pessoas e acompanhar família e amigos?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse" aria-labelledby="faq1h" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary">
                                    O Airlink Locate foi pensado para acompanhamento com consentimento e dentro de círculos (família e amigos). A ideia é
                                    dar tranquilidade e praticidade no dia a dia, com linguagem simples e visual limpo.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item al-card mt-2">
                            <h3 class="accordion-header" id="faq2h">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2"
                                    aria-expanded="false" aria-controls="faq2">
                                    Como funciona a localização em tempo real?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" aria-labelledby="faq2h" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary">
                                    Você vê as mudanças conforme acontecem e também tem um “último visto” para dar contexto. É como ter um mapa ao vivo,
                                    sem telas confusas, focado no que importa.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item al-card mt-2">
                            <h3 class="accordion-header" id="faq3h">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3"
                                    aria-expanded="false" aria-controls="faq3">
                                    Serve para rastrear itens e ajudar a localizar coisas importantes?
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" aria-labelledby="faq3h" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary">
                                    Ele é útil para acompanhar dispositivos e itens associados à sua rotina, e para organizar locais e avisos. O foco é
                                    facilitar o acompanhamento e a localização no dia a dia.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item al-card mt-2">
                            <h3 class="accordion-header" id="faq4h">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4"
                                    aria-expanded="false" aria-controls="faq4">
                                    “Encontrar meu celular” / “Find My” — o Airlink Locate ajuda?
                                </button>
                            </h3>
                            <div id="faq4" class="accordion-collapse collapse" aria-labelledby="faq4h" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary">
                                    Se você procura algo como “encontrar meu celular”, “localizar telefone” ou “Find My”, o Airlink Locate é uma opção
                                    para acompanhar localização e movimentação dentro do seu contexto (família, amigos e círculos), com atualizações em
                                    tempo real e avisos úteis.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item al-card mt-2">
                            <h3 class="accordion-header" id="faq5h">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5"
                                    aria-expanded="false" aria-controls="faq5">
                                    Preciso de conta para usar? É pago?
                                </button>
                            </h3>
                            <div id="faq5" class="accordion-collapse collapse" aria-labelledby="faq5h" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary">
                                    Para usar, é necessário ter uma conta Sacratech iD (gratuita). Ela é usada para autenticar os usuários e manter o
                                    acesso seguro.
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $faqSchema = [
                            '@context' => 'https://schema.org',
                            '@type' => 'FAQPage',
                            'mainEntity' => [
                                [
                                    '@type' => 'Question',
                                    'name' => 'Dá para rastrear pessoas e acompanhar família e amigos?',
                                    'acceptedAnswer' => [
                                        '@type' => 'Answer',
                                        'text' => 'O Airlink Locate foi pensado para acompanhamento com consentimento e dentro de círculos (família e amigos). A ideia é dar tranquilidade e praticidade no dia a dia, com linguagem simples e visual limpo.',
                                    ],
                                ],
                                [
                                    '@type' => 'Question',
                                    'name' => 'Como funciona a localização em tempo real?',
                                    'acceptedAnswer' => [
                                        '@type' => 'Answer',
                                        'text' => 'Você vê as mudanças conforme acontecem e também tem um “último visto” para dar contexto. É como ter um mapa ao vivo, sem telas confusas, focado no que importa.',
                                    ],
                                ],
                                [
                                    '@type' => 'Question',
                                    'name' => 'Serve para rastrear itens e ajudar a localizar coisas importantes?',
                                    'acceptedAnswer' => [
                                        '@type' => 'Answer',
                                        'text' => 'Ele é útil para acompanhar dispositivos e itens associados à sua rotina, e para organizar locais e avisos. O foco é facilitar o acompanhamento e a localização no dia a dia.',
                                    ],
                                ],
                                [
                                    '@type' => 'Question',
                                    'name' => '“Encontrar meu celular” / “Find My” — o Airlink Locate ajuda?',
                                    'acceptedAnswer' => [
                                        '@type' => 'Answer',
                                        'text' => 'Se você procura algo como “encontrar meu celular”, “localizar telefone” ou “Find My”, o Airlink Locate é uma opção para acompanhar localização e movimentação dentro do seu contexto (família, amigos e círculos), com atualizações em tempo real e avisos úteis.',
                                    ],
                                ],
                                [
                                    '@type' => 'Question',
                                    'name' => 'Preciso de conta para usar? É pago?',
                                    'acceptedAnswer' => [
                                        '@type' => 'Answer',
                                        'text' => 'Para usar, é necessário ter uma conta Sacratech iD (gratuita). Ela é usada para autenticar os usuários e manter o acesso seguro.',
                                    ],
                                ],
                            ],
                        ];
                    @endphp

                    <script type="application/ld+json">{{ json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</script>
                </div>
            </div>
        </div>
    </section>
@endsection
