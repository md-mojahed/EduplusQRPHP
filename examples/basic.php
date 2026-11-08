<?php

/**
 * Run from the package folder:
 *
 *     composer install
 *     EDUPLUS_QR_BINARY=/path/to/EduplusQR php examples/basic.php
 *
 * Images are written to examples/output/.
 */

require __DIR__ . '/../vendor/autoload.php';

use Mojahed\EduplusQR\BarCode;
use Mojahed\EduplusQR\Exceptions\GenerationException;
use Mojahed\EduplusQR\QrCode;

$out = __DIR__ . '/output';

// Optional: point at the binary explicitly (otherwise EDUPLUS_QR_BINARY,
// your project's bin/, /usr/local/bin and PATH are searched).
// QrCode::configure(['binary' => '/usr/local/bin/EduplusQR-linux-amd64']);

echo 'EduplusQR ' . QrCode::version() . "\n\n";

// 1. A QR code saved to a file.
$r = QrCode::make('https://eduplus-bd.com')->save("$out/url.png");
echo "1. {$r->path()} ({$r->width()}x{$r->height()} px, QR version {$r->qrVersion()}, ECC {$r->ecc()})\n";

// 2. Styled QR code with a caption, as SVG.
QrCode::make('https://eduplus-bd.com/verify/8F2K')
    ->style('rounded')
    ->color('#0d47a1')
    ->label('Scan to verify')
    ->save("$out/styled.svg");
echo "2. $out/styled.svg\n";

// 3. Wi-Fi QR code (special characters are escaped for you).
QrCode::wifi('Eduplus-Campus', 'p@ss;word')->label('Scan to join Wi-Fi')->save("$out/wifi.png");
echo "3. $out/wifi.png\n";

// 4. Barcodes: Code 128 with text, EAN-13 with the check digit added.
BarCode::make('STU-2025-0142')->showText()->module(3)->save("$out/student.png");
$ean = BarCode::make('590123412345', 'ean13')->save("$out/ean13.png");
echo "4. $out/student.png, $out/ean13.png (encoded {$ean->content()})\n";

// 5. In memory: a data URI for <img src="...">, no file written.
$uri = BarCode::make('ROLL-42')->showText()->toDataUri();
echo '5. data URI, ' . strlen($uri) . " characters\n";

// 6. Errors come back with the exact reason.
try {
    BarCode::make('5901234123458', 'ean13')->toPng();
} catch (GenerationException $e) {
    echo '6. Rejected: ' . $e->getMessage() . "\n";
}

// 7. Batch: many codes in one process, results keyed by your ids.
$results = BarCode::batch()->showText()
    ->add('stu-1', 'STU-0001')
    ->add('stu-2', 'STU-0002')
    ->add('bad', '12', ['type' => 'ean13'])
    ->toBase64();
foreach ($results as $id => $item) {
    echo "7. [$id] " . ($item->ok() ? strlen((string) $item->base64()) . ' base64 chars' : 'error: ' . $item->error()) . "\n";
}

// 8. Printable label sheet: 24 library labels on one A4 page.
$sheet = BarCode::sequence('LIB-{0001..0024}')->showText()->toSheet("$out/labels.pdf", 'a4', '3x8');
echo "8. $out/labels.pdf ({$sheet->pages()} page)\n";
