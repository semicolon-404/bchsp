<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Festivals only (New Year, Children's Day, Chinese New Year, Valentine's, Songkran, Loy Krathong, Christmas): the days of note go. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('festivals')->whereNotIn('theme', ['newyear', 'children', 'chinese', 'valentine', 'songkran', 'loykrathong', 'christmas'])->delete();
    }

    public function down(): void
    {
    }
};
