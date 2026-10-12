<?php

namespace App\Enums;

/** What someone who can sign in to the panel may do there. Nobody else has an account. */
enum Role: string
{
    // Runs the site: settings, menus, people, and everything an author can do.
    case Admin = 'admin';
    // Writes and publishes content: announcements (posters and articles among them), documents.
    // One with a department on the account posts for that department alone (User::postsForOneDepartment()).
    case Author = 'author';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Author => 'Author',
        };
    }
}
