<?php

namespace App\Filament\Concerns;

/**
 * For a panel resource: its names in the reader's language. The menu entry
 * ("Staff"), and the word Filament puts into "Edit staff member" and "New staff member".
 * The English names stay in the class as they were; lang/th.json has the Thai.
 */
trait TranslatesNames
{
    public static function getNavigationLabel(): string
    {
        return __(parent::getNavigationLabel());
    }

    public static function getModelLabel(): string
    {
        return __(parent::getModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        return __(parent::getPluralModelLabel());
    }
}
