<?php

/*
 * ไฟล์ index.php สำหรับโฮสติ้งแบบแชร์ ที่วางโค้ดของเว็บไว้นอก public_html
 * (ดู docs/DEPLOY.th.md หัวข้อ "โฮสติ้งแบบแชร์ ไม่มี SSH")
 *
 * วางไฟล์นี้ในโฟลเดอร์ที่เปิดผ่านเว็บ (เช่น public_html หรือ public_html/new) แทนไฟล์ public/index.php
 * แล้วแก้บรรทัด $app ด้านล่างให้ชี้ไปยังโฟลเดอร์ที่วางโค้ดจริง
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// โฟลเดอร์ที่วางโค้ดของเว็บ (มี app, vendor, storage, .env) อยู่นอก public_html
$code = '/home/ชื่อบัญชี/domains/ชื่อโดเมน/bchsp-app';

if (file_exists($maintenance = $code.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $code.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $code.'/bootstrap/app.php';

// ไฟล์ที่เปิดผ่านเว็บ (build, storage) อยู่ในโฟลเดอร์เดียวกับไฟล์นี้ ไม่ใช่ใน bchsp-app/public
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
