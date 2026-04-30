<footer class="al-footer" data-bs-theme="light">
    <div class="container py-4">
        <div class="row g-4 align-items-start">
            <div class="col-12 col-lg-5">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('airlink-locate-logo-black.png') }}" alt="Airlink Locate" style="height: 24px; width: auto;">
                    <div class="vr"></div>
                    <img src="{{ asset('Sacratech_preto.png') }}" alt="Sacratech" style="height: 22px; width: auto;">
                </div>

                <div class="mt-3 small opacity-75">
                    Airlink, Airlink Locate e todos os serviços Airlink são de propriedade da Sacratech Softwares LTDA.
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="fw-semibold mb-2">Airlink Locate</div>
                        <div class="d-grid gap-1 small">
                            <a href="{{ url('/sobre') }}">Sobre</a>
                            <a href="{{ url('/como-funciona') }}">Como funciona</a>
                            <a href="{{ url('/privacidade') }}">Privacidade</a>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="fw-semibold mb-2">Acesso</div>
                        <div class="d-grid gap-1 small">
                            <a href="{{ route('login') }}">Entrar</a>
                            <a href="{{ url('/dashboard') }}">Painel</a>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="fw-semibold mb-2">Suporte</div>
                        <div class="d-grid gap-1 small">
                            <a href="mailto:suporte@sacratech.com">suporte@sacratech.com</a>
                            <a href="https://sacratech.com" target="_blank" rel="noopener noreferrer">sacratech.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top small d-flex flex-column gap-2 flex-md-row align-items-md-center justify-content-md-between">
            <div>© {{ now()->year }} Sacratech Softwares LTDA. Todos os direitos reservados.</div>
            <div class="opacity-75">Feito para ser simples, bonito e útil no dia a dia.</div>
        </div>
    </div>
</footer>
