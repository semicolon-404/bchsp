<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A krathong a department floats on the home page's river at Loy Krathong, with a short wish. */
class Krathong extends Model
{
    /** Style => what the panel calls it. */
    public const STYLES = ['banana' => 'Banana leaf', 'bread' => 'Bread', 'flower' => 'Flowers', 'lotus' => 'Lotus'];

    protected $fillable = ['festival_id', 'department_id', 'user_id', 'style', 'wish'];

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
