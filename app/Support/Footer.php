<?php

namespace App\Support;

/**
 * The footer of every public page, as an administrator arranged it
 * (System > Site settings > "Footer"): a few words about the hospital, groups
 * of links of the administrator's own, the line of copyright, and whether the
 * count of pages opened is shown.
 *
 * The address, phone numbers and social links in the footer come from the
 * "Contact" and "Social media" settings, and the pages listed there are those
 * that asked to be (System > Pages). Until anything is set here the footer is
 * what it always was.
 */
class Footer
{
    /** How many links of their own an administrator can add. */
    public const MAX_LINKS = 30;

    /** A few words about the hospital under its name, in the reader's language; null when none were written. */
    public static function about(): ?string
    {
        $english = app()->getLocale() === 'en' ? SiteSettings::get('footer.about_en') : null;
        $text = $english ?: SiteSettings::get('footer.about');

        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }

    /**
     * The administrator's own links, under the headings they gave them, in the order they were entered:
     * ['Related agencies' => [['label' => ..., 'url' => ...], ...], ...]. Links without a heading come under "Links".
     *
     * @return array<string, list<array{label: string, url: string}>>
     */
    public static function groups(): array
    {
        $groups = [];

        foreach (self::links() as $link) {
            $groups[$link['group'] !== '' ? $link['group'] : __('Links')][] = ['label' => $link['label'], 'url' => $link['url']];
        }

        return $groups;
    }

    /**
     * The links as kept, cleaned: each with a name and a web address, nothing else.
     *
     * @param  array<int|string, mixed>|null  $rows  what the settings page sent; left out, what is saved
     * @return list<array{group: string, label: string, url: string}>
     */
    public static function links(?array $rows = null): array
    {
        $rows ??= SiteSettings::get('footer.links');
        $links = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $label = is_array($row) ? trim((string) ($row['label'] ?? '')) : '';
            $url = is_array($row) ? trim((string) ($row['url'] ?? '')) : '';

            // Only a web address is ever put into a link.
            if ($label !== '' && preg_match('~^https?://[^\s"\'<>]+$~i', $url)) {
                $links[] = ['group' => trim((string) ($row['group'] ?? '')), 'label' => $label, 'url' => $url];
            }
        }

        return array_slice($links, 0, self::MAX_LINKS);
    }

    /** The line at the very bottom: the administrator's own, or "© year, name of the hospital". */
    public static function copyright(): string
    {
        $own = SiteSettings::get('footer.copyright');

        // The Buddhist year for a reader in Thai.
        return is_string($own) && trim($own) !== ''
            ? trim($own)
            : '© '.(app()->getLocale() === 'th' ? now()->year + 543 : now()->year).' '.SiteSettings::name();
    }

    /** Whether the count of pages opened is shown. On unless switched off. */
    public static function showsVisits(): bool
    {
        return SiteSettings::get('footer.show_visits', true) !== false;
    }
}
