<?php

/*
 * Settings of this site that are not Laravel's own.
 */
return [

    'backup' => [
        // The mysqldump program. On XAMPP for Windows: C:\\xampp\\mysql\\bin\\mysqldump.exe
        // "php", or a server that does not let PHP run programs (shared hosting): the site writes the copy itself.
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

    // For a server where no command can be typed (shared hosting). See docs/DEPLOY.th.md, "โฮสติ้งแบบแชร์".
    // The key that opens /install?key=... once, to set the site up from a browser. At least 16 characters;
    // blank = there is no such page. Remove it from .env when the site is set up.
    'install_key' => env('INSTALL_KEY'),
    // true = the site's daily work (backup, clean-up, the morning email) is done after a visitor's page has
    // been sent, because the server has no scheduler to do it (App\Support\WebCron).
    'schedule_on_visits' => (bool) env('SCHEDULE_ON_VISITS', false),

    'access_log' => [
        // How long a line of the access log is kept before the nightly clean-up removes it. Never fewer than 90 days:
        // Thai law (the Computer Crime Act) asks for traffic logs to be kept at least that long.
        'retention_days' => (int) env('ACCESS_LOG_RETENTION_DAYS', 365),
    ],

    'security' => [
        // Whether everyone who uses the panel must set up two-step sign-in before doing anything else (App\Support\TwoStep).
        'staff_two_step' => (bool) env('STAFF_TWO_STEP', true),
        // Whether what the account of a department writes waits for someone who posts for the whole hospital
        // to approve it before the public sees it (App\Models\Announcement::waitsForApproval()).
        'department_approval' => (bool) env('DEPARTMENT_APPROVAL', true),
    ],

];
