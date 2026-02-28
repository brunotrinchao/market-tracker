<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'Detalhes do produto';

    public string $imageSearchTerm = '';
    public string $manualImageUrl = '';

    /**
     * @var array<int, string>
     */
    public array $imageSearchResults = [];

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->imageSearchTerm = (string) $this->record->name;
        $this->manualImageUrl = (string) ($this->record->image ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pickGoogleImage')
                ->label('Selecionar imagem')
                ->icon('heroicon-o-photo')
                ->color('info')
                ->modalHeading('Selecionar imagem do produto')
                ->modalWidth(Width::FiveExtraLarge)
                ->modalSubmitAction(false)
                ->modalContent(fn () => view('filament.resources.products.actions.google-image-picker')),
            Action::make('editProduct')
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading('Editar produto')
                ->fillForm(fn (): array => [
                    'name' => $this->record->name,
                    'category' => $this->record->category,
                    'image' => $this->record->image,
                ])
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('category')
                        ->label('Categoria')
                        ->maxLength(255),
                    TextInput::make('image')
                        ->label('URL da imagem')
                        ->url()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $this->record->update($data);
                    $this->record->refresh();
                }),
            DeleteAction::make()
                ->label('Excluir')
                ->requiresConfirmation(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ProductPriceChart::class,
        ];
    }

    public function searchGoogleImages(): void
    {
        $query = trim($this->imageSearchTerm);

        if ($query === '') {
            Notification::make()
                ->title('Digite um termo para pesquisar.')
                ->warning()
                ->send();

            return;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0 Safari/537.36',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
            ])
                ->timeout(12)
                ->get('https://www.google.com/search', [
                    'tbm' => 'isch',
                    'q' => $query,
                    'hl' => 'pt-BR',
                ]);

            $html = (string) $response->body();
            $normalizedHtml = str_replace('\\/', '/', $html);
            $urls = [];

            if (preg_match_all('/"ou":"(.*?)"/', $html, $matches)) {
                foreach ($matches[1] as $rawUrl) {
                    $url = $this->decodeEscapedGoogleUrl((string) $rawUrl);

                    if (Str::startsWith($url, ['http://', 'https://'])) {
                        $urls[] = $url;
                    }
                }
            }

            if (empty($urls) && preg_match_all('~https?://encrypted-tbn0\.gstatic\.com/images\?[^"\'\s]+~', $normalizedHtml, $matches)) {
                foreach ($matches[0] as $rawUrl) {
                    $url = $this->decodeEscapedGoogleUrl((string) $rawUrl);

                    if (Str::startsWith($url, ['http://', 'https://'])) {
                        $urls[] = $url;
                    }
                }
            }

            if (empty($urls) && preg_match_all('~https?://[^"\'\s]+\.(?:jpg|jpeg|png|webp)~i', $normalizedHtml, $matches)) {
                foreach ($matches[0] as $rawUrl) {
                    $url = $this->decodeEscapedGoogleUrl((string) $rawUrl);

                    if (Str::startsWith($url, ['http://', 'https://'])) {
                        $urls[] = $url;
                    }
                }
            }

            $this->imageSearchResults = collect($urls)
                ->map(fn (string $url) => trim($url))
                ->filter(fn (string $url) => $url !== '')
                ->unique()
                ->take(24)
                ->values()
                ->all();

            if (empty($this->imageSearchResults)) {
                Notification::make()
                    ->title('Nenhuma imagem encontrada para esse termo.')
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $exception) {
            $this->imageSearchResults = [];

            Notification::make()
                ->title('Não foi possível buscar imagens no momento.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function decodeEscapedGoogleUrl(string $rawUrl): string
    {
        $raw = trim($rawUrl);
        $decodedByJson = json_decode('"' . addslashes($raw) . '"');
        $url = is_string($decodedByJson) ? $decodedByJson : $raw;

        $url = str_replace(['\\u003d', '\\u0026', '\\/', '&amp;'], ['=', '&', '/', '&'], $url);
        $url = stripcslashes($url);

        return html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
    }

    public function selectGoogleImage(string $encodedUrl): void
    {
        $url = rawurldecode($encodedUrl);

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            Notification::make()
                ->title('URL de imagem inválida.')
                ->danger()
                ->send();

            return;
        }

        $this->record->update([
            'image' => $url,
        ]);

        $this->record->refresh();

        Notification::make()
            ->title('Imagem do produto atualizada com sucesso.')
            ->success()
            ->send();

        $this->unmountAction();
    }

    public function saveManualImageUrl(): void
    {
        $url = trim($this->manualImageUrl);

        if ($url === '' || ! Str::startsWith($url, ['http://', 'https://'])) {
            Notification::make()
                ->title('Informe uma URL válida (http/https).')
                ->warning()
                ->send();

            return;
        }

        $this->record->update([
            'image' => $url,
        ]);

        $this->record->refresh();
        $this->manualImageUrl = $url;

        Notification::make()
            ->title('Imagem do produto atualizada com sucesso.')
            ->success()
            ->send();
    }
}
