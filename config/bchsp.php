<?php

/*
 * Settings of this site that are not Laravel's own.
 */
return [

    'backup' => [
        // The mysqldump program. On XAMPP for Windows: C:\\xampp\\mysql\\bin\\mysqldump.exe
        'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),
        // The database connection to copy. Blank = the one the site uses.
        'connection' => env('BACKUP_CONNECTION'),
        // A second folder the copies are repeated in, off this disk: another drive, a network share, or a folder
        // that a sync program sends away. Blank = the copies are in one place only.
        'copy_to' => env('BACKUP_COPY_TO'),
        // How many days a nightly copy is kept before it is removed.
        'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),
        // Where the copies go. Blank = storage/app/backups, which the web server does not hand out.
        'folder' => env('BACKUP_FOLDER'),
    ],

    'access_log' => [
        // How long a line of the access log is kept before the nightly clean-up removes it. Never fewer than 90 days:
        // Thai law (the Computer Crime Act) asks for traffic logs to be kept at least that long.
        'retention_days' => (int) env('ACCESS_LOG_RETENTION_DAYS', 365),
    ],

    'security' => [
        // Whether everyone who uses the panel must set up two-step sign-in before doing anything else (App\Support\TwoStep).
        'staff_two_step' => (bool) env('STAFF_TWO_STEP', true),
    ],

];
