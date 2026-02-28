# Market Tracker

Sistema web para monitorar preços de produtos por supermercado, importar notas fiscais (PDF), comparar melhores ofertas e montar listas de compra com estimativa de menor custo.

A aplicação foi construída com Laravel + Filament e foco em uso administrativo rápido por modal.

## Principais funcionalidades

- Cadastro e gestão de:
  - Produtos
  - Supermercados (com endereço e coordenadas)
  - Notas fiscais e itens
  - Listas de compra
- Importação de nota fiscal via PDF (parser NFC-e)
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
- `app/Services/Parsers/NfceMgParser.php`: parser de PDF NFC-e
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
- O parser NFC-e atual está orientado ao cenário de desenvolvimento e pode exigir ajustes para layouts reais diferentes de PDF.

## Licença

Projeto interno para estudo/produto. Ajuste esta seção conforme sua estratégia de distribuição.
