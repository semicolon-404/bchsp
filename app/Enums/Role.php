<?php

namespace App\Enums;

/** What someone who can sign in to the panel may do there. Nobody else has an account. */
enum Role: string
{
    // Runs the site: settings, menus, people, and everything an author can do.
    case Admin = 'admin';
    // Writes and publishes content: announcements, articles, posters, documents.
    case Author = 'author';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Author => 'Author',
        };
    }
}
