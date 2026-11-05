<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A krathong floated at Loy Krathong, with a short wish: by a department in the panel, or by a visitor at
 * /loykrathong under a name of their choosing. An administrator can take one off the river (is_hidden).
 */
class Krathong extends Model
{
    /** Style => what the panel calls it. */
    public const STYLES = ['banana' => 'Banana leaf', 'bread' => 'Bread', 'flower' => 'Flowers', 'lotus' => 'Lotus'];

    protected $fillable = ['festival_id', 'department_id', 'user_id', 'name', 'style', 'wish', 'is_hidden'];

    protected function casts(): array
    {
        return ['is_hidden' => 'boolean'];
    }

    /** Who floated it: the department, or the name a visitor gave. */
    public function by(): string
    {
        return $this->department?->localName() ?? ($this->name ?: __('The hospital'));
    }

    public function festival(): BelongsTo
    {
        return $this->belongsTo(Festival::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
