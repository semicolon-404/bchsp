<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\TranslatesTitle;
use App\Models\Announcement;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * How to use the panel, for the staff who post: written for someone who has never used it, and
 * read from the menu at any time. What it says follows who is reading: the account of a department
 * is told about its own page and about approval; those who post for the whole hospital, about approving.
 * (The text itself is in the view, in Thai: the panel is used in Thai.)
 */
class Guide extends Page
{
    use TranslatesTitle;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'How to use this panel';

    protected static ?string $title = 'How to use this panel';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.guide';

    /** @return array{department: ?string, forDepartment: bool, approval: bool, admin: bool} */
    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'department' => $user->department?->name,
            'forDepartment' => $user->postsForOneDepartment(),
            'approval' => Announcement::waitsForApproval($user),
            'admin' => $user->isAdmin(),
        ];
    }
}
