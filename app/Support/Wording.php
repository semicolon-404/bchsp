<?php

namespace App\Support;

use Illuminate\Translation\Translator;

/**
 * Words on the site that an administrator has rewritten (System > Site settings >
 * "Wording"), in Thai, in English, or both, without touching the language files.
 *
 * Every phrase the site shows goes through the translator, keyed by its
 * English original. An administrator picks a phrase by searching for it as it
 * reads on the site, and writes what it should say instead. Whatever they
 * wrote wins over the language file; a phrase left alone reads as it always did.
 *
 * Placeholders such as :name and :count keep working if they are kept in the
 * new wording.
 */
class Wording extends Translator
{
    private const SETTING = 'wording';

    /** How many phrases can be rewritten. Plenty for headings and labels; not a second language file. */
    public const MAX = 200;

    /** @var array<string, array<string, string>>|null [locale => [English original => new wording]] */
    private static ?array $rewritten = null;

    /** @var array<string, string>|null every phrase with its Thai: [English original => Thai] */
    private static ?array $phrases = null;

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $line = self::rewritten()[$locale ?? $this->locale][$key] ?? null;

        return $line !== null ? $this->makeReplacements($line, $replace) : parent::get($key, $replace, $locale, $fallback);
    }

    /**
     * The rows as saved: [['key' => English original, 'th' => ?string, 'en' => ?string], ...].
     *
     * @return list<array{key: string, th: ?string, en: ?string}>
     */
    public static function rows(): array
    {
        $rows = SiteSettings::get(self::SETTING);

        return is_array($rows) ? self::clean($rows) : [];
    }

    /**
     * Rows fit to save, whatever the form sent: known phrases only, each once, with something written.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<array{key: string, th: ?string, en: ?string}>
     */
    public static function clean(array $rows): array
    {
        $clean = [];

        foreach ($rows as $row) {
            $key = is_array($row) ? ($row['key'] ?? null) : null;

            if (! is_string($key) || ! isset(self::phrases()[$key]) || isset($clean[$key])) {
                continue;
            }

            $th = trim((string) ($row['th'] ?? ''));
            $en = trim((string) ($row['en'] ?? ''));

            if ($th !== '' || $en !== '') {
                $clean[$key] = ['key' => $key, 'th' => $th === '' ? null : $th, 'en' => $en === '' ? null : $en];
            }
        }

        return array_slice(array_values($clean), 0, self::MAX);
    }

    /** Forget what was read, so the next phrase asked for sees what was just saved. */
    public static function refresh(): void
    {
        self::$rewritten = null;
    }

    /**
     * Phrases whose English or Thai contains what was typed: [English original => "English — Thai"].
     *
     * @return array<string, string>
     */
    public static function search(string $term, int $limit = 40): array
    {
        $term = mb_strtolower(trim($term));
        $found = [];

        if (mb_strlen($term) < 2) {
            return $found;
        }

        foreach (self::phrases() as $english => $thai) {
            if (str_contains(mb_strtolower($english), $term) || str_contains(mb_strtolower($thai), $term)) {
                $found[$english] = self::label($english);

                if (count($found) >= $limit) {
                    break;
                }
            }
        }

        return $found;
    }

    /** How a phrase is shown in the picker: both languages, shortened. */
    public static function label(string $english): string
    {
        $thai = self::phrases()[$english] ?? null;

        return str($english)->limit(90).($thai ? ' — '.str($thai)->limit(70) : '');
    }

    /** @return array<string, array<string, string>> */
    private static function rewritten(): array
    {
        if (self::$rewritten !== null) {
            return self::$rewritten;
        }

        $rewritten = ['th' => [], 'en' => []];

        foreach ((array) SiteSettings::get(self::SETTING, []) as $row) {
            foreach (['th', 'en'] as $locale) {
                if (is_array($row) && filled($row[$locale] ?? null) && is_string($row['key'] ?? null)) {
                    $rewritten[$locale][$row['key']] = $row[$locale];
                }
            }
        }

        return self::$rewritten = $rewritten;
    }

    /** @return array<string, string> */
    private static function phrases(): array
    {
        return self::$phrases ??= (array) json_decode((string) @file_get_contents(lang_path('th.json')), true);
    }
}
