@extends('layouts.site')

@section('title', 'Localização necessária - Airlink Locate')

@section('content')
    <div class="container py-5 py-md-6">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="al-card p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <i class="mdi mdi-map-marker-alert-outline fs-3"></i>
                        <div>
                            <div class="h4 mb-0 fw-semibold">A localização é necessária</div>
                            <div class="text-secondary mt-1">O Airlink Locate depende de localização para exibir mapa, atualizações e alertas.</div>
                        </div>
                    </div>

                    <div class="text-secondary mt-3">
                        Você pode continuar navegando, mas recursos essenciais não funcionarão corretamente sem acesso à localização. Para ativar, permita o
                        acesso nas permissões do seu navegador/sistema e tente novamente.
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <a href="{{ route('dashboard') }}" class="al-btn-secondary text-decoration-none">Voltar ao painel</a>
                        <button type="button" class="al-btn-primary" id="tryLocation">Tentar ativar localização</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const btn = document.getElementById('tryLocation');
            if (!btn) return;

            btn.addEventListener('click', () => {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition(
                    () => {
                        localStorage.setItem('airlink_location_allowed', '1');
                        window.location.href = '{{ route('dashboard') }}';
                    },
                    () => {
                        localStorage.setItem('airlink_location_allowed', '0');
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            });
        })();
    </script>
@endsection

