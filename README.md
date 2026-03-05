# Market Tracker

Sistema web para monitorar preços de produtos por supermercado, importar notas fiscais (PDF), comparar melhores ofertas e montar listas de compra com estimativa de menor custo.

A aplicação foi construída com Laravel + Filament e foco em uso administrativo rápido por modal.

## Principais funcionalidades

- Cadastro e gestão de:
  - Produtos
  - Supermercados (com endereço e coordenadas)
  - Notas fiscais e itens
  - Listas de compra
- Importação de nota fiscal por leitura de QR Code (câmera)
- Histórico de preços por produto e por supermercado
- Dashboard com indicadores e gráficos relevantes:
  - KPIs gerais
  - Evolução de cesta fixa de produtos
  - Gasto por supermercado
  - Preço médio por categoria
  - Tabela de oportunidades de preço
- Mapa de supermercados com ação para abrir o resource do mercado
- Fluxo CRUD orientado a modais (adicionar/editar/excluir)

## Stack

- PHP 8.2+
- Laravel 12
- Filament 5
- Livewire + Alpine.js
- Vite + Tailwind CSS
- Banco: SQLite (padrão) ou MySQL/PostgreSQL
- Parser de PDF: `smalot/pdfparser`
- IA de extração: Cloudflare Workers AI (com fallback regex)

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+ e npm
- Extensões PHP comuns do Laravel (pdo, mbstring, openssl, tokenizer, etc.)

## Instalação rápida

1. Clonar o projeto

```bash
git clone <repo-url>
cd market-tracker
```

2. Instalar dependências backend

```bash
composer install
```

3. Preparar ambiente

```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar banco no `.env`

Exemplo SQLite:

```env
DB_CONNECTION=sqlite
```

Crie o arquivo se necessário:

```bash
touch database/database.sqlite
```

5. Rodar migrations e seeders

```bash
php artisan migrate --seed
```

6. Instalar dependências frontend e buildar

```bash
npm install
npm run build
```

7. Subir o projeto

```bash
composer run dev
```

Ou separadamente:

```bash
php artisan serve
npm run dev
```

## Acesso

- URL local: `http://127.0.0.1:8000` (ou a exibida pelo `artisan serve`)
- Usuário seed padrão:
  - Email: `test@example.com`
  - Senha: `password`

## Seed de dados de demonstração

O seeder principal (`MarketTrackerSeeder`) cria:

- Produtos com categorias e preço base
- Mercados com endereço e coordenadas
- Relacionamentos mercado-produto
- Notas históricas com itens e variação de preço simulada

Rodar novamente:

```bash
php artisan db:seed --class=MarketTrackerSeeder
```

## Upload de PDF (notas)

Se aparecer erro de upload no Livewire (ex.: `failed to upload`), aumente limites do PHP:

```bash
php -d upload_max_filesize=20M -d post_max_size=20M artisan serve
```

Ou configure no `php.ini`:

- `upload_max_filesize`
- `post_max_size`

## Importação por QR Code

- Ao clicar em `Importar nota`, a câmera é aberta no modal para leitura do QR Code da NFC-e.
- A URL lida no QR é consultada pelo backend e o retorno completo é enviado para a IA extrair:
  - mercado
  - data de emissão
  - valor total
  - itens/produtos

## Configuração da IA (Cloudflare)

Defina no `.env`:

```env
CLOUDFLARE_AI_ACCOUNT_ID=seu-account-id
CLOUDFLARE_AI_API_TOKEN=seu-token
CLOUDFLARE_AI_MODEL=@cf/meta/llama-3-8b-instruct
CLOUDFLARE_AI_TIMEOUT=90
CLOUDFLARE_AI_MAX_RETRIES=2
CLOUDFLARE_AI_INITIAL_BACKOFF_MS=1200
CLOUDFLARE_AI_MAX_SOURCE_CHARS=12000
CLOUDFLARE_AI_FALLBACK_REGEX=true
```

- `CLOUDFLARE_AI_FALLBACK_REGEX=true` ativa fallback para parser regex legado se a IA falhar.
- Sem `CLOUDFLARE_AI_ACCOUNT_ID` e `CLOUDFLARE_AI_API_TOKEN`, o fluxo usa somente parser regex.

## Consulta de nota pela chave de acesso

Para usar chave de acesso (44 dígitos) como fonte principal dos dados da nota, configure:

```env
NFCE_LOOKUP_URL_TEMPLATE=https://seu-endpoint/nfce/{access_key}
NFCE_LOOKUP_TOKEN=
NFCE_LOOKUP_TOKEN_HEADER=Authorization
NFCE_LOOKUP_TOKEN_PREFIX="Bearer "
NFCE_LOOKUP_TIMEOUT=30
```

Regras do fluxo:
- Se a chave for informada, a aplicação consulta os dados da nota por chave.
- Se enviar PDF sem chave, o sistema tenta extrair a chave do PDF.
- Se a consulta por chave falhar e houver PDF, usa extração por IA no PDF como fallback.
- Mercado, emissão, valor e itens passam a vir da nota fiscal (não de preenchimento manual).

## Geolocalização e mapa no celular

Navegadores exigem **contexto seguro** para geolocalização:

- `https://...`
- ou `http://localhost` (alguns casos locais)

Para testar em dispositivo físico, use túnel HTTPS (ex.: ngrok) e configure:

```env
APP_URL=https://seu-dominio.ngrok-free.dev
```

O projeto já força `https` nas URLs quando `APP_URL` começa com `https://`.

## Estrutura funcional (resumo)

- `app/Filament/Resources`: CRUDs administrativos
- `app/Filament/Widgets`: widgets e gráficos do dashboard
- `app/Filament/Pages/Dashboard.php`: dashboard principal + mapa
- `app/Services/InvoiceService.php`: processamento/enriquecimento da nota
- `app/Services/Parsers/GeminiNfceParser.php`: extração estruturada por IA (Cloudflare + fallback)
- `app/Services/Parsers/NfceMgParser.php`: parser regex legado (fallback opcional)
- `database/seeders`: dados de demonstração

## Comandos úteis

```bash
# Rodar testes
composer test

# Limpar cache de config/rotas/views
php artisan optimize:clear

# Rebuild de assets
npm run build
```

## Observações

- Algumas integrações externas (consulta CNPJ/geocoding) dependem de conectividade e chave quando aplicável.
- A qualidade da extração depende da legibilidade do PDF/retorno da URL da NFC-e e do modelo de IA configurado.

## Licença

Projeto interno para estudo/produto. Ajuste esta seção conforme sua estratégia de distribuição.
