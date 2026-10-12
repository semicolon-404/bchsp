<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A question people ask the hospital, with its answer, at /faq: what to bring, when to
 * visit, how to ask for a record. Written once by staff, so the phone rings less.
 * Plain text: an answer is a few lines, not a page.
 */
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory, InTwoLanguages;

    protected $fillable = ['question', 'question_en', 'answer', 'answer_en', 'group', 'is_active', 'sort_order'];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Shown on the page, in the order staff gave them. */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function localQuestion(): string
    {
        return $this->inLanguage('question');
    }

    public function localAnswer(): string
    {
        return $this->inLanguage('answer');
    }
}
