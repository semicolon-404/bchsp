<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/** For a panel page of our own: its title and menu entry in the reader's language. */
trait TranslatesTitle
{
    public static function getNavigationLabel(): string
    {
        return __(parent::getNavigationLabel());
    }

    public function getTitle(): string|Htmlable
    {
        $title = parent::getTitle();

        return is_string($title) ? __($title) : $title;
    }
}
