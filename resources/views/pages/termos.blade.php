@extends('layouts.site')

@section('title', 'Termos de Uso - Airlink Locate')

@section('content')
    <div class="container py-5 py-md-6">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <div class="h1 fw-semibold mb-3">Termos de Uso</div>
                <div class="text-secondary mb-4">
                    Última atualização: {{ now()->format('d/m/Y') }}
                </div>

                <div class="al-card p-4 p-md-5">
                    <div class="text-secondary">
                        <div class="mb-4">
                            Estes Termos de Uso (“Termos”) regem o acesso e o uso do Airlink Locate (“Serviço”), disponibilizado pela Sacratech Softwares
                            (“Sacratech”, “nós”). Ao acessar, criar conta, autenticar-se, instalar, utilizar ou de qualquer forma interagir com o Serviço,
                            você (“Usuário”, “você”) declara que leu, compreendeu e concorda integralmente com estes Termos.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">1. Finalidade do Serviço</div>
                        <div class="mb-4">
                            O Airlink Locate foi projetado para permitir o compartilhamento e a visualização de localização em tempo real e históricos
                            recentes, com foco em utilidade, segurança e conveniência em contextos legítimos, tais como acompanhamento consentido entre
                            familiares, grupos (“famílias” e “círculos”) e conexões diretas.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">2. Consentimento e uso responsável</div>
                        <div class="mb-4">
                            O uso do Serviço depende de consentimento. Você se compromete a obter e manter autorização válida das pessoas cujas localizações
                            forem compartilhadas/visualizadas, bem como a respeitar decisões de aceitar, recusar ou revogar convites. É proibido utilizar o
                            Serviço para perseguição, vigilância não autorizada, assédio, invasão de privacidade ou qualquer finalidade ilícita.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">3. Conta e autenticação</div>
                        <div class="mb-4">
                            Para utilizar o Serviço, pode ser necessária autenticação via Sacratech iD. Você é responsável por manter a segurança de suas
                            credenciais, ativar medidas de proteção como 2FA quando disponível e notificar imediatamente qualquer suspeita de acesso indevido.
                            A Sacratech poderá adotar medidas de segurança e limitação de acesso para proteger Usuários e infraestrutura.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">4. Compartilhamento por convite</div>
                        <div class="mb-4">
                            O compartilhamento no Airlink Locate ocorre por convites. Quem recebe um convite pode aceitar ou recusar. Você reconhece que a
                            aceitação é voluntária e que o convite pode expirar, ser revogado ou ser recusado. O Usuário também pode sair de famílias/círculos
                            ou remover conexões, quando disponível, encerrando o compartilhamento.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">5. Localização e permissões do dispositivo</div>
                        <div class="mb-4">
                            Para o Serviço funcionar como esperado, você pode precisar permitir o acesso à localização do seu dispositivo. Você pode negar,
                            porém isso pode limitar ou impedir recursos essenciais (como mapa e atualizações). Você é responsável por configurar permissões,
                            sistemas operacionais, planos de energia, conectividade e quaisquer requisitos técnicos para o correto funcionamento.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">6. Conteúdo, dados e propriedade</div>
                        <div class="mb-4">
                            O Serviço e seus componentes (interface, APIs, marca, design, código, textos e sinais distintivos) pertencem à Sacratech ou a seus
                            licenciadores e são protegidos por leis de propriedade intelectual. É proibida a engenharia reversa, exploração indevida, abuso de
                            APIs, scraping, tentativa de acesso não autorizado, ou qualquer uso que comprometa a segurança do sistema.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">7. Segurança, disponibilidade e limitações</div>
                        <div class="mb-4">
                            Empregamos boas práticas de segurança, mas nenhum sistema é infalível. O Serviço pode sofrer indisponibilidades, atualizações,
                            manutenções, interrupções e limitações por fatores externos (conectividade, GPS, sistema operacional, restrições do navegador etc.).
                            Você concorda que o Serviço é fornecido “como está”, dentro de esforços razoáveis de estabilidade e proteção.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">8. Proibições e condutas vedadas</div>
                        <div class="mb-4">
                            É vedado: (a) usar o Serviço sem consentimento; (b) tentar burlar controles de acesso; (c) enviar conteúdo malicioso; (d) explorar
                            vulnerabilidades; (e) comercializar ou revender acesso; (f) usar o Serviço para violar direitos de terceiros; (g) utilizar
                            indevidamente localizações para fins lucrativos, discriminatórios, de vigilância ou perseguição.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">9. Privacidade e proteção de dados</div>
                        <div class="mb-4">
                            O tratamento de dados, incluindo dados de localização, é regido também pela nossa Política de Privacidade. A Sacratech declara que
                            não utiliza nem divulga localizações para finalidades lucrativas, publicidade comportamental ou venda de dados. O compartilhamento
                            ocorre apenas nas relações criadas pelo Usuário (convites aceitos) e conforme configurações e permissões do dispositivo.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">10. Medidas contra abuso</div>
                        <div class="mb-4">
                            Para proteger o Serviço e Usuários, podemos aplicar controles como rate limiting, detecção de fraude, bloqueios, revogações e
                            suspensão/encerramento de acesso, especialmente em caso de violação destes Termos, suspeita de abuso ou exigência legal.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">11. Responsabilidades do Usuário</div>
                        <div class="mb-4">
                            Você é responsável por: (a) utilizar o Serviço somente com consentimento; (b) manter suas permissões e configurações do sistema
                            operacional de modo compatível com o Serviço; (c) proteger seu dispositivo e credenciais; (d) agir de boa-fé e reportar incidentes
                            e suspeitas de abuso; (e) garantir que informações fornecidas por você sejam verdadeiras e atualizadas.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">12. Limitação de responsabilidade</div>
                        <div class="mb-4">
                            Na máxima extensão permitida pela legislação aplicável, a Sacratech não se responsabiliza por danos indiretos, lucros cessantes,
                            perda de dados, interrupções, falhas decorrentes de terceiros, do seu dispositivo, do sistema operacional, do provedor de internet,
                            do GPS, de restrições do navegador ou por uso indevido do Serviço por qualquer pessoa. O Serviço não substitui medidas de segurança
                            pública, emergências, autoridade policial ou serviços essenciais.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">13. Indenização</div>
                        <div class="mb-4">
                            Você concorda em indenizar a Sacratech contra reivindicações, perdas, responsabilidades e despesas decorrentes de (a) uso sem
                            consentimento; (b) violação destes Termos; (c) violação de direitos de terceiros; (d) atos ilícitos praticados por você no uso do
                            Serviço.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">14. Suspensão, encerramento e preservação de evidências</div>
                        <div class="mb-4">
                            Podemos suspender ou encerrar acessos quando necessário para proteger Usuários, cumprir obrigações legais, responder a incidentes de
                            segurança ou mitigar abuso. Registros técnicos podem ser preservados para fins de auditoria, segurança e cumprimento legal.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">15. Alterações</div>
                        <div class="mb-4">
                            Podemos atualizar estes Termos periodicamente. Alterações relevantes poderão ser comunicadas no Serviço. O uso continuado após a
                            atualização indica concordância com a versão vigente.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">16. Contato</div>
                        <div>
                            Para dúvidas, solicitações e suporte, entre em contato pelos canais oficiais da Sacratech Softwares.
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-secondary small">
                    Este documento é informativo e visa estabelecer regras de uso responsável do serviço.
                </div>
            </div>
        </div>
    </div>
@endsection

