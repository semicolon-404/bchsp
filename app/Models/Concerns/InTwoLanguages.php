<?php

namespace App\Models\Concerns;

/**
 * For content written in Thai with an optional English version beside it:
 * "title" and "title_en", "body" and "body_en". A reader in English gets the
 * English where some was written, and the Thai where none was.
 */
trait InTwoLanguages
{
    public function inLanguage(string $column): string
    {
        $english = app()->getLocale() === 'en' ? $this->getAttribute($column.'_en') : null;

        // An editor left empty still holds "<p></p>".
        return (string) (filled(trim(strip_tags((string) $english))) ? $english : $this->getAttribute($column));
    }
}
