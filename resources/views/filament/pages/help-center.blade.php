<x-filament-panels::page>
    <style>
        .hc-wrap { display: grid; gap: 16px; }
        .hc-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 16px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04); }
        .hc-title { margin: 0; font-size: 22px; line-height: 1.2; color: #0f172a; }
        .hc-muted { margin: 6px 0 0; font-size: 13px; color: #6b7280; }
        .hc-grid { display: grid; gap: 10px; }
        .hc-grid-hero { grid-template-columns: 1fr; }
        .hc-grid-filter { grid-template-columns: 1fr; }
        .hc-actions { display: grid; gap: 8px; grid-template-columns: 1fr; margin-top: 10px; }
        .hc-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; border-radius: 10px; padding: 8px 12px; border: 1px solid #cbd5e1; font-size: 13px; font-weight: 700; text-decoration: none; }
        .hc-btn-green { background: #ecfdf5; color: #047857; border-color: #86efac; }
        .hc-btn-blue { background: #eff6ff; color: #1d4ed8; border-color: #93c5fd; }
        .hc-btn-amber { background: #fffbeb; color: #b45309; border-color: #fcd34d; }
        .hc-label { display: grid; gap: 5px; }
        .hc-label span { font-size: 11px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .hc-input, .hc-select { width: 100%; min-height: 40px; border: 1px solid #d1d5db; border-radius: 10px; padding: 8px 10px; font-size: 14px; color: #111827; background: #fff; }
        .hc-counter { margin-top: 8px; font-size: 12px; color: #6b7280; }
        .hc-session-nav { display: grid; gap: 8px; margin-top: 12px; }
        .hc-session-link { display: block; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; text-decoration: none; background: #fff; }
        .hc-session-link strong { display: block; font-size: 13px; color: #111827; }
        .hc-session-link span { display: block; margin-top: 4px; font-size: 12px; color: #6b7280; }
        .hc-head-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .hc-subtitle { margin: 0; font-size: 16px; color: #111827; }
        .hc-progress-track { margin-top: 10px; height: 8px; width: 100%; background: #f3f4f6; border-radius: 999px; overflow: hidden; }
        .hc-progress-bar { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #059669 0%, #10b981 100%); transition: width .2s ease; }
        .hc-checks { margin-top: 12px; display: grid; gap: 8px; }
        .hc-check-item { display: flex; gap: 10px; align-items: flex-start; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; }
        .hc-check-item input { margin-top: 2px; width: 16px; height: 16px; }
        .hc-check-item span { font-size: 13px; color: #374151; }
        .hc-sections { display: grid; gap: 10px; }
        .hc-section { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 16px; background: #fff; }
        .hc-toggle { width: 100%; border: 0; background: #fff; display: flex; gap: 10px; align-items: flex-start; justify-content: space-between; padding: 14px; text-align: left; cursor: pointer; }
        .hc-badge { display: inline-flex; border-radius: 8px; background: #f3f4f6; color: #4b5563; font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 2px 8px; }
        .hc-toggle h4 { margin: 6px 0 4px; font-size: 16px; color: #111827; }
        .hc-toggle p { margin: 0; font-size: 12px; color: #6b7280; }
        .hc-arrow { color: #9ca3af; transition: transform .2s ease; }
        .hc-arrow.open { transform: rotate(180deg); }
        .hc-body { border-top: 1px solid #f3f4f6; padding: 12px 14px; }
        .hc-steps { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
        .hc-step { border: 1px solid #f3f4f6; border-radius: 10px; padding: 10px; background: #f9fafb; }
        .hc-step-tag { font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: 700; letter-spacing: .04em; }
        .hc-step p { margin: 4px 0 0; font-size: 14px; color: #374151; line-height: 1.45; }

        @media (min-width: 900px) {
            .hc-grid-hero { grid-template-columns: 1.7fr 1fr; align-items: center; }
            .hc-actions { margin-top: 0; }
            .hc-grid-filter { grid-template-columns: 1.5fr 1fr; }
        }
    </style>

    <div x-data="helpCenterData()" class="hc-wrap">
        <section class="hc-card">
            <div class="hc-grid hc-grid-hero">
                <div>
                    <h2 class="hc-title">Tutorial completo de uso</h2>
                    <p class="hc-muted">Esta pagina explica o fluxo completo: cadastro inicial, importacao de cupom/nota, gestao de produtos, listas de compra, compartilhamento e uso no mobile.</p>
                </div>
                <div class="hc-actions">
                    <a href="/invoices/create" class="hc-btn hc-btn-green">Importar cupom</a>
                    <a href="/shopping-lists/create" class="hc-btn hc-btn-blue">Criar lista</a>
                    <a href="/products" class="hc-btn hc-btn-amber">Ver produtos</a>
                </div>
            </div>
        </section>

        <section class="hc-card">
            <div class="hc-grid hc-grid-filter">
                <label class="hc-label">
                    <span>Buscar no tutorial</span>
                    <input type="text" x-model.debounce.250ms="query" placeholder="Ex: importacao, mercado, compartilhamento..." class="hc-input">
                </label>
                <label class="hc-label">
                    <span>Filtrar por tema</span>
                    <select x-model="category" class="hc-select">
                        <option value="all">Todos os temas</option>
                        <template x-for="cat in categories" :key="cat">
                            <option :value="cat" x-text="cat"></option>
                        </template>
                    </select>
                </label>
            </div>
            <div class="hc-counter"><span x-text="filteredSections.length"></span> topicos encontrados.</div>
            <div class="hc-session-nav">
                <template x-for="section in filteredSections" :key="'nav-' + section.id">
                    <a
                        href="#"
                        class="hc-session-link"
                        @click.prevent="openOnlySection(section.id)"
                    >
                        <strong x-text="section.title"></strong>
                        <span x-text="section.summary"></span>
                    </a>
                </template>
            </div>
        </section>

        <section class="hc-card">
            <div class="hc-head-row">
                <h3 class="hc-subtitle">Checklist rapido de implantacao</h3>
                <span class="hc-counter" x-text="doneCount + '/' + checklist.length + ' concluido(s)'"></span>
            </div>
            <div class="hc-progress-track">
                <div class="hc-progress-bar" :style="'width:' + progressPercent + '%'" ></div>
            </div>
            <div class="hc-checks">
                <template x-for="item in checklist" :key="item.id">
                    <label class="hc-check-item">
                        <input type="checkbox" :checked="item.done" @change="toggleChecklist(item.id)">
                        <span x-text="item.label"></span>
                    </label>
                </template>
            </div>
        </section>

        <section class="hc-sections">
            <template x-for="section in filteredSections" :key="section.id">
                <article class="hc-section" :id="'help-section-' + section.id">
                    <button type="button" class="hc-toggle" @click="toggleSection(section.id)">
                        <div>
                            <span class="hc-badge" x-text="section.category"></span>
                            <h4 x-text="section.title"></h4>
                            <p x-text="section.summary"></p>
                        </div>
                        <span class="hc-arrow" :class="openSections.includes(section.id) ? 'open' : ''">▼</span>
                    </button>
                    <div x-show="openSections.includes(section.id)" class="hc-body">
                        <ol class="hc-steps">
                            <template x-for="(step, index) in section.steps" :key="section.id + '-' + index">
                                <li class="hc-step">
                                    <span class="hc-step-tag" x-text="'Passo ' + (index + 1)"></span>
                                    <p x-text="step"></p>
                                </li>
                            </template>
                        </ol>
                    </div>
                </article>
            </template>
        </section>
    </div>

    <script>
        function helpCenterData() {
            const STORAGE_KEY = 'market-tracker-help-checklist-v1';
            const savedChecklist = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');

            const sections = [
                {
                    id: 'inicio',
                    category: 'Primeiros passos',
                    title: 'Sessao 1: Configuracao inicial do sistema',
                    summary: 'Como preparar o ambiente para comecar sem inconsistencias.',
                    steps: [
                        'Acesse o painel e valide se o login esta funcionando corretamente, preferencialmente com sua conta principal do Google.',
                        'No menu de Mercados, cadastre os supermercados que voce realmente usa, incluindo endereco completo para habilitar mapa e agrupamentos.',
                        'No menu de Categorias, revise as categorias padrao e crie novas somente quando necessario para manter os relatorios limpos.',
                        'No menu de Produtos, faca uma revisao rapida para corrigir nomes duplicados ou muito genericos antes de iniciar importacoes em massa.'
                    ],
                },
                {
                    id: 'importacao',
                    category: 'Importacao',
                    title: 'Sessao 2: Fluxo recomendado de importacao de cupom/nota',
                    summary: 'Como cadastrar produtos e precos pelo fluxo oficial.',
                    steps: [
                        'Clique em Importar cupom/nota e use o QR Code/chave de acesso da NFC-e para preencher os itens automaticamente.',
                        'Verifique o mercado identificado na importacao e ajuste o endereco se estiver incompleto para melhorar o mapa.',
                        'Confirme os itens importados, mantendo unidade e quantidade corretas; isso melhora os calculos de preco por produto.',
                        'Salve a nota e revise rapidamente os itens com preco fora do esperado para evitar distorcoes nos comparativos.'
                    ],
                },
                {
                    id: 'listas',
                    category: 'Lista de compras',
                    title: 'Sessao 3: Criacao e gestao da lista de compras',
                    summary: 'Como montar listas orientadas por menor preco e mercado.',
                    steps: [
                        'Crie uma nova lista informando nome, data e observacoes para organizar o planejamento da compra da semana.',
                        'Adicione produtos na lista; quando houver historico de preco, o sistema sugere mercado mais barato.',
                        'Na coluna Onde comprar, clique no mercado para abrir as opcoes de preco e selecionar manualmente se desejar.',
                        'Durante o uso no celular, marque os itens como feitos para acompanhar progresso e facilitar execucao no mercado.'
                    ],
                },
                {
                    id: 'lista-compartilhada',
                    category: 'Lista compartilhada',
                    title: 'Sessao 4: Uso da lista de compras compartilhada',
                    summary: 'Passo a passo para quem recebe o link e para quem administra a lista.',
                    steps: [
                        'Abra a lista no painel e clique em Compartilhar lista para gerar o link publico.',
                        'Envie o link para a pessoa que fara a compra; ela nao precisa autenticar.',
                        'No link publico, toque no nome do mercado para expandir/recolher itens.',
                        'Marque os produtos concluídos no checkbox para mover para a secao Feitos.',
                        'Use o botao + no mercado correto para adicionar novo item naquela loja.',
                        'Na busca, digite pelo menos 2 caracteres para localizar produtos daquele mercado.',
                        'Se nao encontrar, clique em Cadastrar produto e informe nome/quantidade.',
                        'Se tiver codigo de barras, use Ler codigo de barras; se a camera falhar, digite manualmente.',
                        'Ao remover um item, confirme no modal; a remocao reflete na lista original do painel.',
                        'Acompanhe a barra de progresso no topo para ver percentual concluido da compra.'
                    ],
                },
                {
                    id: 'compartilhamento',
                    category: 'Compartilhamento',
                    title: 'Sessao 5: Compartilhamento publico da lista',
                    summary: 'Como enviar lista por link sem exigir autenticacao.',
                    steps: [
                        'Abra a lista e use Compartilhar lista para gerar o link publico com token seguro.',
                        'Envie o link para quem vai comprar; a pessoa consegue marcar itens, adicionar produtos e remover itens.',
                        'As alteracoes no link publico refletem na lista original do painel, mantendo sincronizacao em tempo real.',
                        'Se necessario, gere novo link ao recriar o token para bloquear acessos antigos.'
                    ],
                },
                {
                    id: 'mobile',
                    category: 'Uso no mobile',
                    title: 'Sessao 6: Boas praticas para uso no celular',
                    summary: 'Como obter melhor experiencia durante a compra.',
                    steps: [
                        'Use navegador atualizado (Chrome ou Safari recente) e permita camera/localizacao quando solicitado.',
                        'No leitor de codigo de barras, prefira a camera traseira e boa iluminacao para melhorar a leitura.',
                        'Se a leitura automatica falhar, use o campo de codigo manual no proprio modal para continuar sem interromper fluxo.',
                        'Mantenha poucos apps abertos durante a compra para reduzir travamentos e melhorar rapidez no preenchimento.'
                    ],
                },
                {
                    id: 'qualidade-dados',
                    category: 'Qualidade de dados',
                    title: 'Sessao 7: Como manter dados confiaveis',
                    summary: 'Padroes para evitar ruido em precos e produtos.',
                    steps: [
                        'Evite criar produto manualmente quando ele ja existir; busque pelo nome ou codigo primeiro.',
                        'Revise produtos sem imagem e sem codigo de barras para enriquecer cadastro e facilitar identificacao no mobile.',
                        'Padronize nomes (ex.: Arroz 5kg) e evite sufixos desnecessarios para simplificar buscas.',
                        'Periodicamente, remova duplicidades e valide categorias para manter relatorios mais precisos.'
                    ],
                },
            ];

            return {
                query: '',
                category: 'all',
                sections,
                openSections: ['inicio'],
                checklist: [
                    { id: 'c1', label: 'Mercados principais cadastrados com endereco completo', done: savedChecklist.includes('c1') },
                    { id: 'c2', label: 'Primeira importacao de cupom/nota realizada com sucesso', done: savedChecklist.includes('c2') },
                    { id: 'c3', label: 'Lista de compras criada com data e observacoes', done: savedChecklist.includes('c3') },
                    { id: 'c4', label: 'Compartilhamento publico da lista testado no celular', done: savedChecklist.includes('c4') },
                    { id: 'c5', label: 'Leitura de codigo de barras testada (automatica ou manual)', done: savedChecklist.includes('c5') },
                ],

                get categories() {
                    return [...new Set(this.sections.map((section) => section.category))];
                },

                get filteredSections() {
                    const term = this.query.trim().toLowerCase();

                    return this.sections.filter((section) => {
                        const matchCategory = this.category === 'all' || section.category === this.category;
                        if (!matchCategory) return false;
                        if (term === '') return true;

                        const haystack = [section.title, section.summary, section.category, ...section.steps].join(' ').toLowerCase();
                        return haystack.includes(term);
                    });
                },

                get doneCount() {
                    return this.checklist.filter((item) => item.done).length;
                },

                get progressPercent() {
                    if (this.checklist.length === 0) return 0;
                    return Math.round((this.doneCount / this.checklist.length) * 100);
                },

                toggleSection(sectionId) {
                    if (this.openSections.includes(sectionId)) {
                        this.openSections = this.openSections.filter((id) => id !== sectionId);
                        return;
                    }
                    this.openSections.push(sectionId);
                },

                openOnlySection(sectionId) {
                    this.openSections = [sectionId];
                    const sectionEl = document.getElementById('help-section-' + sectionId);
                    if (sectionEl) {
                        sectionEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                },

                toggleChecklist(checklistId) {
                    this.checklist = this.checklist.map((item) => item.id !== checklistId ? item : { ...item, done: !item.done });
                    const doneIds = this.checklist.filter((item) => item.done).map((item) => item.id);
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(doneIds));
                },
            };
        }
    </script>
</x-filament-panels::page>
