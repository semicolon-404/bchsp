<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * The last part of a page's address, made from its title.
 *
 * Laravel's Str::slug() transliterates to ASCII, which turns a Thai title into
 * an empty string. This one keeps the letters of any language.
 */
class Slug
{
    public static function make(string $text, string $whenEmpty = 'page'): string
    {
        $slug = mb_strtolower(trim($text));
        // Keep letters, combining marks (Thai vowels and tone marks) and digits; everything else becomes a dash.
        $slug = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $slug);
        $slug = trim((string) $slug, '-');

        return mb_substr($slug, 0, 150) ?: $whenEmpty;
    }

    /**
     * The slug for a record, with "-2", "-3"... added until no other record of its kind has it.
     *
     * @param  list<string>  $taken  words the address cannot be, because a page of the site already answers to them
     */
    public static function unique(Model $record, string $text, string $whenEmpty = 'page', array $taken = []): string
    {
        $base = self::make($text, $whenEmpty);
        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $taken, true) || $record->newQuery()->where('slug', $slug)->when($record->exists, fn ($query) => $query->whereKeyNot($record->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
