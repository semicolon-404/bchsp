<?php

namespace Tests\Unit;

use App\Support\RichText;
use Tests\TestCase;

/** Text from the editor made into a page, including text the editor would not have written itself. */
class RichTextTest extends TestCase
{
    public function test_a_bold_inside_a_bold_does_not_break_the_page(): void
    {
        // As the old site's page of past directors had it.
        $html = RichText::html('<table><tbody><tr><td><strong>ก.ค. 2568&nbsp;<strong>(รักษาการ)</strong></strong></td></tr></tbody></table>');

        $this->assertStringContainsString('(รักษาการ)', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertSame(1, substr_count($html, '<strong>'));
    }

    public function test_ordinary_text_is_left_as_it_is_and_nothing_is_nothing(): void
    {
        $this->assertStringContainsString('<p><strong>Bold</strong> and <em>slanted</em></p>', RichText::html('<p><strong>Bold</strong> and <em>slanted</em></p>'));
        $this->assertSame('', RichText::html(null));
        $this->assertSame('', RichText::html(''));
    }

    public function test_only_marks_inside_their_own_kind_are_taken_away(): void
    {
        $this->assertSame('<p><strong>a <em>b</em> c</strong></p>', RichText::withoutNestedMarks('<p><strong>a <strong><em>b</em></strong> c</strong></p>'));
    }
}
