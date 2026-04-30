@extends('layouts.site')

@section('title', 'Entrar - Airlink Locate')

@section('content')
    @php
        $stage = $stage ?? 'email';
        $email = $email ?? '';
        $airlinkIcon = file_exists(public_path('airlink-icon-white.png')) ? 'airlink-icon-white.png' : 'airlink-locate-logo-white.png';
    @endphp

    <section class="position-relative overflow-hidden">
        <div class="al-hero-glow"></div>
        <div class="al-hero-glow-2"></div>

        <div class="container py-5 py-md-6 position-relative">
            <div class="row justify-content-center">
                <div class="col-12 col-md-7 col-lg-5">
                    <div class="al-oauth-card al-auth-card p-4 p-md-5" data-stage="{{ $stage }}">
                        <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
                            <img src="{{ asset('SacratechID-Icon.png') }}" alt="Sacratech iD" style="height: 36px; width: auto;">
                            <div class="vr opacity-25"></div>
                            <img src="{{ asset($airlinkIcon) }}" alt="Airlink" style="height: 28px; width: auto;">
                        </div>

                        <div class="text-center mb-4">
                            @if ($stage === 'email')
                                <div class="h3 mb-1 fw-semibold">Entrar</div>
                                <div class="text-secondary">Use seu email da Sacratech iD</div>
                            @elseif ($stage === 'password')
                                <div class="h3 mb-1 fw-semibold">Senha</div>
                                <div class="text-secondary">Confirme para continuar</div>
                            @else
                                <div class="h3 mb-1 fw-semibold">Verificação</div>
                                <div class="text-secondary">Digite o código do app autenticador</div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('login.perform') }}" class="vstack gap-3">
                            @csrf
                            <input type="hidden" name="stage" value="{{ $stage }}">

                            @if ($stage === 'email')
                                <div>
                                    <label class="form-label" for="email">Email</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                                        class="form-control form-control-lg rounded-4 al-input @error('email') is-invalid @enderror" />
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @elseif ($stage === 'password')
                                <div>
                                    <label class="form-label" for="email_display">Email</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input id="email_display" type="email" value="{{ $email }}" disabled
                                            class="form-control form-control-lg rounded-4 al-input opacity-75" />
                                        <a class="al-nav-btn al-nav-btn-secondary" href="{{ route('login', ['reset' => 1]) }}">
                                            Trocar
                                        </a>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label" for="password">Senha</label>
                                    <input id="password" name="password" type="password" required autocomplete="current-password"
                                        class="form-control form-control-lg rounded-4 al-input @error('password') is-invalid @enderror" />
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <div>
                                    <label class="form-label" for="email_display">Email</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input id="email_display" type="email" value="{{ $email }}" disabled
                                            class="form-control form-control-lg rounded-4 al-input opacity-75" />
                                        <a class="al-nav-btn al-nav-btn-secondary" href="{{ route('login', ['reset' => 1]) }}">
                                            Trocar
                                        </a>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">Código do app autenticador</label>
                                    <input id="two_factor_code" name="two_factor_code" type="hidden" value="">
                                    <div class="d-flex gap-2 justify-content-between" id="alOtp">
                                        @for ($i = 0; $i < 6; $i++)
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code"
                                                class="form-control form-control-lg rounded-4 al-input al-otp-input @error('two_factor_code') is-invalid @enderror" />
                                        @endfor
                                    </div>
                                    @error('two_factor_code')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            <button type="submit" class="w-100 al-btn-primary al-auth-submit">
                                <span class="al-auth-submit__spinner spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                                @if ($stage === 'email')
                                    Continuar
                                @elseif ($stage === 'password')
                                    Entrar
                                @else
                                    Confirmar
                                @endif
                            </button>

                            <div class="d-grid gap-2">
                                <a class="w-100 al-btn-secondary text-decoration-none d-inline-flex align-items-center justify-content-center gap-2"
                                    href="https://account-id.sacratech.com" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ asset('SacratechID-Icon.png') }}" alt="" style="height: 18px; width: auto;">
                                    Criar conta Sacratech iD
                                    <i class="mdi mdi-open-in-new"></i>
                                </a>
                            </div>

                            <div class="small text-secondary text-center mt-1">
                                Ao entrar, você concorda em usar o Airlink Locate de forma responsável e com consentimento.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const card = document.querySelector('.al-auth-card');
            const form = card?.querySelector('form');
            const submitButton = form?.querySelector('.al-auth-submit');
            const spinner = submitButton?.querySelector('.al-auth-submit__spinner');

            if (card) {
                requestAnimationFrame(() => card.classList.add('al-auth-card--enter'));
            }

            if (!form || !submitButton) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();

                if (card?.dataset?.stage === '2fa') {
                    const inputs = [...(card.querySelectorAll('.al-otp-input') || [])];
                    const code = inputs.map((i) => (i.value || '').replace(/\D/g, '')).join('').slice(0, 8);
                    const hidden = form.querySelector('#two_factor_code');
                    if (hidden) hidden.value = code;
                }

                submitButton.setAttribute('disabled', 'disabled');
                if (spinner) spinner.classList.remove('d-none');
                if (card) card.classList.add('al-auth-card--leave');

                window.setTimeout(() => form.submit(), 180);
            });

            if (card?.dataset?.stage === '2fa') {
                const inputs = [...(card.querySelectorAll('.al-otp-input') || [])];
                const hidden = form.querySelector('#two_factor_code');
                let autoSubmitting = false;

                const sync = () => {
                    const code = inputs.map((i) => (i.value || '').replace(/\D/g, '')).join('').slice(0, 8);
                    if (hidden) hidden.value = code;

                    const complete = inputs.every((i) => ((i.value || '').replace(/\D/g, '').length === 1));
                    if (complete && code.length >= 6 && !autoSubmitting) {
                        autoSubmitting = true;
                        window.setTimeout(() => form.requestSubmit(), 120);
                    }
                };

                const focusAt = (idx) => {
                    const el = inputs[idx];
                    if (el) el.focus();
                };

                inputs.forEach((input, idx) => {
                    input.addEventListener('input', () => {
                        const v = (input.value || '').replace(/\D/g, '').slice(0, 1);
                        input.value = v;
                        sync();
                        if (v && idx < inputs.length - 1) focusAt(idx + 1);
                    });

                    input.addEventListener('keydown', (ev) => {
                        if (ev.key === 'Backspace' && !input.value && idx > 0) {
                            focusAt(idx - 1);
                        }
                        if (ev.key === 'ArrowLeft' && idx > 0) focusAt(idx - 1);
                        if (ev.key === 'ArrowRight' && idx < inputs.length - 1) focusAt(idx + 1);
                    });

                    input.addEventListener('paste', (ev) => {
                        const text = (ev.clipboardData?.getData('text') || '').replace(/\D/g, '');
                        if (!text) return;
                        ev.preventDefault();
                        for (let i = 0; i < inputs.length; i++) {
                            inputs[i].value = text[i] ? text[i] : '';
                        }
                        sync();
                        const next = Math.min(text.length, inputs.length - 1);
                        focusAt(next);
                    });
                });

                window.setTimeout(() => focusAt(0), 50);
            }
        })();
    </script>
@endsection
