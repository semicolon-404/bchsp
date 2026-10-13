<?php

namespace App\Http\Controllers;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * /qr.svg?to=/announcements/...: a QR code that leads to a page of this site, to put on a printed poster
 * or a sign at a counter. Drawn here, as a picture of lines (SVG) that prints sharp at any size; nothing is
 * asked of another server. Only this site's own addresses are drawn: the code cannot be made to lead elsewhere.
 */
class QrController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $to = $request->query('to');

        // A path of this site: one slash at the start, no host, no way round by "\" or control characters.
        abort_unless(is_string($to) && strlen($to) <= 600 && ! str_contains($to, '\\') && preg_match('~^/(?!/)\S*$~u', $to) === 1, 404);

        $svg = (new QRCode(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgAddXmlHeader' => false,
            'drawLightModules' => true,
        ])))->render(url($to));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="qr.svg"',
        ]);
    }
}
