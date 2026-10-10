<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * A file the public can download from /downloads: a form, a price list, a
 * regulation. Listed under a heading staff give it.
 */
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, InTwoLanguages;

    /**
     * What can be offered for download => what the list calls it. No format that can carry a program
     * or a macro (.docm, .xlsm, .exe, .html): a visitor trusts a file because the hospital offers it.
     */
    public const TYPES = [
        'application/pdf' => 'PDF',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint',
    ];

    protected $fillable = ['title', 'title_en', 'group', 'file_path', 'file_name', 'is_active', 'sort_order'];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The file belongs to the document alone, so it goes when the document does or when it is replaced.
        static::updated(function (Document $document) {
            if ($document->wasChanged('file_path') && $document->getOriginal('file_path')) {
                Storage::disk('public')->delete($document->getOriginal('file_path'));
            }
        });

        static::deleted(fn (Document $document) => Storage::disk('public')->delete($document->file_path));
    }

    /** Offered on the site, in staff's order. */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('title');
    }

    public function localTitle(): string
    {
        return $this->inLanguage('title');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /** "PDF", "Word"...: from the file's ending, which is what a visitor's computer goes by too. */
    public function kind(): string
    {
        return match (strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION))) {
            'pdf' => 'PDF',
            'docx' => 'Word',
            'xlsx' => 'Excel',
            'pptx' => 'PowerPoint',
            default => strtoupper(pathinfo($this->file_path, PATHINFO_EXTENSION)),
        };
    }

    /** "1.2 MB", or null when the file is no longer there. */
    public function size(): ?string
    {
        $disk = Storage::disk('public');

        return $disk->exists($this->file_path) ? Number::fileSize($disk->size($this->file_path)) : null;
    }
}
