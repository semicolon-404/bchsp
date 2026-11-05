<?php

namespace App\Support;

/**
 * Words a public page will not take (the krathongs visitors float, App\Http\Controllers\KrathongController): rude
 * words in Thai and English, and links. A first line only: an administrator can take off what gets past it.
 */
class RudeWords
{
    private const WORDS = [
        // Only whole rude words that are not also inside ordinary ones (not "กู", as in "กู้ภัย"; not "สัด", as in "สัดส่วน").
        'เหี้ย', 'สัส', 'ควย', 'แตด', 'เย็ด', 'มึง', 'ไอ้สัตว์', 'อีดอก', 'อีสัตว์', 'ส้นตีน',
        'ระยำ', 'ชาติหมา', 'ตอแหล', 'กะหรี่', 'ดอกทอง', 'จัญไร',
        'fuck', 'shit', 'bitch', 'cunt', 'pussy', 'asshole', 'bastard', 'slut', 'whore',
    ];

    /** Whether the text has a rude word or a link in it; spaces and dots between letters do not hide one. */
    public static function in(string $text): bool
    {
        $plain = mb_strtolower(preg_replace('/[\s.\-_*]+/u', '', $text));

        if (preg_match('~https?://|www\.|\.(com|net|org|co|th|ly|io)\b~i', $text)) {
            return true;
        }

        foreach (self::WORDS as $word) {
            if (str_contains($plain, $word)) {
                return true;
            }
        }

        return false;
    }
}
