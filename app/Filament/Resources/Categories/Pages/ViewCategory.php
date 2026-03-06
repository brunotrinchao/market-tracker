<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected static ?string $title = 'Detalhes da categoria';

    protected function getHeaderActions(): array
    {
        return [
            $this->makeEditCategoryAction('editCategoryDesktop')
                ->extraAttributes(['class' => 'mt-desktop-only']),
            $this->makeDeleteCategoryAction('deleteCategoryDesktop')
                ->extraAttributes(['class' => 'mt-desktop-only']),
            ActionGroup::make([
                $this->makeEditCategoryAction('editCategoryMobile'),
                $this->makeDeleteCategoryAction('deleteCategoryMobile'),
            ])
                ->icon('heroicon-o-ellipsis-vertical')
                ->tooltip('Ações')
                ->color('gray')
                ->extraAttributes(['class' => 'mt-mobile-only']),
        ];
    }

    private function makeEditCategoryAction(string $name): Action
    {
        return Action::make($name)
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->slideOver()
                ->modalHeading('Editar categoria')
                ->fillForm(fn (): array => [
                    'name' => $this->record->name,
                    'slug' => $this->record->slug,
                    'keywords' => $this->record->keywords ?? [],
                ])
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255),
                    TagsInput::make('keywords')
                        ->label('Palavras-chave'),
                ])
                ->action(function (array $data): void {
                    if (empty($data['slug']) && ! empty($data['name'])) {
                        $data['slug'] = Str::slug((string) $data['name']);
                    }

                    $this->record->update($data);
                    $this->record->refresh();
                });
    }

    private function makeDeleteCategoryAction(string $name): DeleteAction
    {
        return DeleteAction::make($name)
            ->label('Excluir')
            ->requiresConfirmation();
    }
}
