{{--
    /install?key=...: setting the site up from a browser (App\Http\Controllers\InstallController). Stands on its
    own: the site's stylesheet and its layout need a database that does not exist yet. Shown in Thai and English
    together: the language switch needs a session, and there is none here.
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>ติดตั้งเว็บไซต์ · Set up the website</title>
    <style>
        body { margin: 0; padding: 24px; background: #f4f4f5; color: #18181b; font-family: 'Sarabun', 'Noto Sans Thai', system-ui, sans-serif; font-size: 1.05rem; line-height: 1.7; }
        main { max-width: 42rem; margin: 0 auto; background: #fff; border: 1px solid #d4d4d8; border-radius: 12px; padding: 24px 28px; }
        h1 { margin: 0 0 4px; font-size: 1.6rem; }
        h2 { margin: 28px 0 8px; font-size: 1.15rem; }
        small { color: #52525b; }
        ul { list-style: none; margin: 0; padding: 0; }
        li { display: flex; gap: 12px; align-items: baseline; padding: 8px 0; border-bottom: 1px solid #e4e4e7; }
        li span:first-child { flex: 0 0 4.5rem; font-weight: 700; }
        .ok { color: #15803d; } .bad { color: #b91c1c; } .skip { color: #71717a; }
        .said { display: block; color: #52525b; font-size: .9rem; white-space: pre-wrap; word-break: break-word; }
        label { display: block; margin-top: 14px; font-weight: 600; }
        input { width: 100%; box-sizing: border-box; margin-top: 4px; padding: 10px 12px; border: 1px solid #a1a1aa; border-radius: 8px; font: inherit; }
        button { margin-top: 20px; padding: 12px 24px; border: 0; border-radius: 8px; background: #15803d; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .box { margin-top: 16px; padding: 12px 16px; border-radius: 8px; border: 1px solid; }
        .box.bad { background: #fef2f2; } .box.ok { background: #f0fdf4; }
        code { background: #f4f4f5; padding: 1px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<main>
    <h1>ติดตั้งเว็บไซต์</h1>
    <small>Set up the website</small>

    @if ($steps !== null)
        <h2>ผลการติดตั้ง · What was done</h2>
        <ul>
            @foreach ($steps as $step)
                <li>
                    <span class="{{ ['done' => 'ok', 'failed' => 'bad', 'skipped' => 'skip'][$step['state']] }}">{{ ['done' => 'สำเร็จ', 'failed' => 'ล้มเหลว', 'skipped' => 'ข้าม'][$step['state']] }}</span>
                    <span>{{ __($step['name']) }}<span class="said">{{ $step['said'] }}</span></span>
                </li>
            @endforeach
        </ul>

        @if ($failed)
            <div class="box bad">ติดตั้งไม่สำเร็จ แก้ตามข้อความด้านบนแล้วเปิดหน้านี้ใหม่ ขั้นที่สำเร็จแล้วทำซ้ำได้โดยไม่เสียหาย<br><small>It did not finish. Put right what the message says and open this page again: the steps already done can be done again safely.</small></div>
        @else
            <div class="box ok">
                <strong>ติดตั้งเสร็จแล้ว</strong> ทำสองอย่างนี้ต่อทันที:
                <ol>
                    <li style="display: list-item; border: 0; padding: 2px 0">ลบบรรทัด <code>INSTALL_KEY</code> ออกจากไฟล์ <code>.env</code> (หน้านี้ปิดตัวเองแล้ว แต่ไม่ควรทิ้งกุญแจไว้)</li>
                    <li style="display: list-item; border: 0; padding: 2px 0">เข้าหลังบ้านที่ <a href="{{ url('/admin') }}">{{ url('/admin') }}</a> แล้วดูหน้า ระบบ &gt; สถานะระบบ</li>
                </ol>
                <small>Done. Now remove INSTALL_KEY from .env, then sign in at /admin and look at System &gt; System status.</small>
            </div>
        @endif
    @else
        <h2>1. ตรวจเซิร์ฟเวอร์ · The server</h2>
        <ul>
            @foreach ($checks as $check)
                <li>
                    <span class="{{ $check['ok'] ? 'ok' : 'bad' }}">{{ $check['ok'] ? 'ผ่าน' : 'ไม่ผ่าน' }}</span>
                    <span>{{ __($check['name']) }}<span class="said">{{ $check['found'] }}</span></span>
                </li>
            @endforeach
        </ul>

        @if ($errors)
            <div class="box bad">
                @foreach ($errors as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <h2>2. ผู้ดูแลระบบคนแรก · The first administrator</h2>
        <form method="post" action="{{ url('/install') }}">
            <input type="hidden" name="key" value="{{ $key }}">
            <label>ชื่อ · Name
                <input name="name" value="{{ $old['name'] ?? '' }}" required maxlength="255" autocomplete="name">
            </label>
            <label>ชื่อผู้ใช้ (ภาษาอังกฤษ ไม่เว้นวรรค) · Username
                <input name="username" value="{{ $old['username'] ?? '' }}" required maxlength="50" autocomplete="username" autocapitalize="none">
            </label>
            <label>รหัสผ่าน (อย่างน้อย 8 ตัวอักษร) · Password
                <input name="password" type="password" required minlength="8" autocomplete="new-password">
            </label>
            <button>ติดตั้ง · Set up</button>
        </form>
        <p><small>จะสร้างตารางในฐานข้อมูล ใส่ข้อมูลตั้งต้น (รายชื่อหน่วยงาน ประเภทประกาศ) เชื่อมโฟลเดอร์ไฟล์อัปโหลด และสร้างบัญชีนี้ ข้อมูลที่มีอยู่แล้วจะไม่ถูกลบ</small></p>
    @endif
</main>
</body>
</html>
