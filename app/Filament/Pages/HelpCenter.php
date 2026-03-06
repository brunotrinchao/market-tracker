<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class HelpCenter extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Ajuda';

    protected static ?string $title = 'Central de Ajuda';

    protected static ?string $slug = 'ajuda';

    protected static string | UnitEnum | null $navigationGroup = 'Suporte';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.help-center';

    public function getBreadcrumb(): string
    {
        return 'Ajuda';
    }
}
