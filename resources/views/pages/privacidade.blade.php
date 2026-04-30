@extends('layouts.site')

@section('title', 'Privacidade - Airlink Locate')

@section('content')
    <div class="container py-5 py-md-6">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <div class="h1 fw-semibold mb-3">Política de Privacidade</div>
                <div class="text-secondary mb-4">
                    Última atualização: {{ now()->format('d/m/Y') }}
                </div>

                <div class="al-card p-4 p-md-5">
                    <div class="text-secondary">
                        <div class="mb-4">
                            Esta Política de Privacidade (“Política”) descreve como a Sacratech Softwares (“Sacratech”, “nós”) coleta, utiliza, armazena e
                            protege dados no Airlink Locate (“Serviço”). O Serviço lida com dados sensíveis por natureza (especialmente localização),
                            portanto aplicamos princípios de minimização, finalidade, segurança e controle pelo Usuário.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">1. Controlador e contato</div>
                        <div class="mb-4">
                            A Sacratech Softwares é a responsável pelo Serviço. Solicitações relacionadas a privacidade, segurança e direitos do titular podem
                            ser encaminhadas pelos canais oficiais de atendimento.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">2. Dados que tratamos</div>
                        <div class="mb-4">
                            Podemos tratar: (a) dados de conta (nome, email e identificadores); (b) dados de autenticação e segurança (ex.: registros de
                            sessão, logs de acesso e eventos de segurança); (c) dados de dispositivos (identificadores, último status e sinal de presença);
                            (d) dados de localização (latitude/longitude, horário e contexto de compartilhamento); (e) dados de uso (ações no app, convites,
                            aceites e recusas) para operação e auditoria.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">3. Finalidades</div>
                        <div class="mb-4">
                            Utilizamos dados para: (a) autenticar e manter o acesso seguro; (b) permitir compartilhamento de localização somente entre
                            Usuários conectados por convite aceito; (c) fornecer recursos como mapa, alertas, eventos e histórico recente; (d) prevenir abuso,
                            fraude e acessos indevidos; (e) melhorar estabilidade e desempenho.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">4. Bases legais e conformidade</div>
                        <div class="mb-4">
                            O tratamento de dados pode ocorrer com base em consentimento (especialmente para compartilhamento por convite e permissões do
                            dispositivo), cumprimento de obrigação legal/regulatória, execução de contrato (prestação do Serviço), exercício regular de direitos
                            (segurança e prevenção de fraude) e legítimo interesse (estabilidade, auditoria e proteção do sistema), sempre com avaliação de
                            necessidade e proporcionalidade.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">4. Consentimento e controle do Usuário</div>
                        <div class="mb-4">
                            O compartilhamento ocorre por convites com aceitar/recusar. Você pode encerrar compartilhamentos ao sair de grupos, remover
                            conexões e revogar permissões do dispositivo. Você é responsável por garantir consentimento válido antes de compartilhar ou
                            visualizar localização de terceiros.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">5. Declaração de não comercialização de localizações</div>
                        <div class="mb-4">
                            A Sacratech não utiliza nem divulga localizações para fins lucrativos, venda de dados, publicidade comportamental ou quaisquer
                            atividades de monetização baseadas em localização. A localização é tratada estritamente para viabilizar o funcionamento do Serviço
                            conforme as conexões criadas pelos Usuários.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">6. Compartilhamento com terceiros</div>
                        <div class="mb-4">
                            Não compartilhamos localizações com terceiros para fins comerciais. Podemos compartilhar dados estritamente necessários com
                            provedores de infraestrutura (ex.: hospedagem, banco de dados, monitoramento) sob obrigação contratual de confidencialidade e
                            segurança. Também poderemos divulgar informações quando exigido por lei, ordem judicial ou para proteger direitos e segurança.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">7. Retenção</div>
                        <div class="mb-4">
                            Mantemos dados pelo tempo necessário para cumprir as finalidades do Serviço, requisitos legais e segurança. Dados de localização
                            podem ser armazenados por períodos limitados, conforme regras do produto e obrigações operacionais.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">8. Segurança</div>
                        <div class="mb-4">
                            Adotamos medidas técnicas e organizacionais como autenticação, hashing de senhas, 2FA quando disponível, proteção contra abuso,
                            controles de acesso, criptografia em trânsito quando aplicável e auditoria. Ainda assim, nenhum sistema é absolutamente imune e o
                            Usuário deve proteger suas credenciais e dispositivos.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">9. Cookies, sessões e dados técnicos</div>
                        <div class="mb-4">
                            Utilizamos cookies e identificadores de sessão para manter autenticação, segurança e funcionamento. Também podemos registrar dados
                            técnicos (como IP, user-agent e eventos de segurança) para prevenir fraude, detectar abuso e manter integridade do Serviço.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">10. Direitos do titular</div>
                        <div class="mb-4">
                            Você pode solicitar informações, correção, exclusão e outras medidas conforme legislação aplicável. Algumas solicitações podem ser
                            limitadas por obrigações legais, segurança e prevenção à fraude.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">11. Incidentes de segurança</div>
                        <div class="mb-4">
                            Em caso de incidentes relevantes, adotaremos medidas para conter, investigar e mitigar impactos. Poderemos comunicar Usuários e
                            autoridades quando necessário, considerando obrigação legal e riscos envolvidos.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">12. Crianças e adolescentes</div>
                        <div class="mb-4">
                            O Serviço deve ser utilizado com responsabilidade. Quando aplicável, o uso por menores deve ocorrer com ciência e supervisão do
                            responsável legal, respeitando leis e regulamentos aplicáveis.
                        </div>

                        <div class="h4 text-white fw-semibold mb-2">13. Alterações</div>
                        <div>
                            Podemos atualizar esta Política periodicamente. O uso continuado do Serviço após alterações indica ciência da versão vigente.
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-secondary small">
                    Para consultar também os Termos de Uso, acesse <a href="{{ route('terms') }}">Termos de Uso</a>.
                </div>
            </div>
        </div>
    </div>
@endsection
