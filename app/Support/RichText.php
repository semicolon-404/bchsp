<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Throwable;

/**
 * Text written in the panel's editor, made into HTML for a page (an announcement, a page of text).
 *
 * The editor's own renderer is strict about what it reads, and some text that came from elsewhere (the old
 * site, a paste from Word) is not what it would have written: a bold inside a bold, for one, makes it fail
 * and the whole page with it. So such text is tidied first, and if it still cannot be read, the words are
 * shown plainly rather than not at all.
 */
class RichText
{
    /** The marks that mean nothing more when one is inside another of its kind. */
    private const MARKS = ['strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'code'];

    public static function html(?string $content): string
    {
        if (blank($content)) {
            return '';
        }

        try {
            return self::render($content);
        } catch (Throwable) {
            // Tidied, it is tried once more.
        }

        try {
            return self::render(self::withoutNestedMarks($content));
        } catch (Throwable $e) {
            report($e);

            // The words, at least, in their paragraphs.
            return collect(preg_split('/\R{2,}/u', trim(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />', '</tr>', '</li>'], "\n\n", $content)))))
                ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim(html_entity_decode($paragraph, ENT_QUOTES | ENT_HTML5)))).'</p>')
                ->implode('');
        }
    }

    private static function render(string $content): string
    {
        return RichContentRenderer::make($content)->fileAttachmentsDisk('public')->toHtml();
    }

    /** The same text with every mark that sits inside another of its kind taken away, keeping what it held. */
    public static function withoutNestedMarks(string $content): string
    {
        $document = new DOMDocument;
        $wrapped = '<?xml encoding="UTF-8"><div id="rich-text-root">'.$content.'</div>';

        if (! @$document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR)) {
            return $content;
        }

        $xpath = new DOMXPath($document);

        foreach (self::MARKS as $mark) {
            // The deepest first, so that one unwrapped does not leave another behind.
            $inner = array_reverse(iterator_to_array($xpath->query("//{$mark}[ancestor::{$mark}]")));

            foreach ($inner as $element) {
                /** @var DOMElement $element */
                while ($element->firstChild) {
                    $element->parentNode->insertBefore($element->firstChild, $element);
                }

                $element->parentNode->removeChild($element);
            }
        }

        $root = $document->getElementById('rich-text-root');
        $html = '';

        foreach ($root?->childNodes ?? [] as $child) {
            $html .= $document->saveHTML($child);
        }

        return $html;
    }
}
