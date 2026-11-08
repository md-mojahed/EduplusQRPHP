# EduplusQR PHP SDK

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Fast QR code **and** barcode generation for **PHP 7.4+**, powered by the standalone EduplusQR binary. You get PNG, SVG or PDF output, 15 code types, logos and styles, Wi-Fi, vCard and payment content, and batches with in-memory images, files, a ZIP or printable label sheets. It needs no GD, Imagick or any other PHP extension.

```php
use Mojahed\EduplusQR\QrCode;
use Mojahed\EduplusQR\BarCode;

QrCode::make('https://eduplus-bd.com/verify/8F2K')->style('rounded')->save('qr.png');
BarCode::make('STU-2025-0142')->showText()->toDataUri();      // <img src="...">
QrCode::wifi('Campus', 'secret')->send();                     // straight to the browser
```

> Using Laravel? Use [`eduplus/qr`](https://github.com/md-mojahed) instead. It has the same API plus config, an install command and a health check.

## Contents

- [Installation](#installation)
- [Single codes](#single-codes)
- [Getting the output](#getting-the-output)
- [Options](#options)
- [Content formats](#content-formats)
- [Batches (bulk generation)](#batches-bulk-generation)
- [Errors](#errors)
- [Configuration](#configuration)
- [Upgrading from 1.0.1](#upgrading-from-101)

## Installation

```bash
composer require mojahed/eduplusqr
```

The package doesn't bundle the binary (that used to add about 15 MB to every project). Install it once per server:

```bash
sudo cp EduplusQR-linux-amd64 /usr/local/bin/
sudo chmod +x /usr/local/bin/EduplusQR-linux-amd64
```

The binary is found automatically. The package looks for the file named for your machine (`EduplusQR-linux-amd64`, `EduplusQR-linux-arm64`, `EduplusQR-darwin-arm64`, `EduplusQR-windows-amd64.exe`, …) or a plain `EduplusQR`, in this order:

1. `QrCode::configure(['binary' => '/full/path'])`
2. the `EDUPLUS_QR_BINARY` environment variable
3. your project's `bin/` folder, which works on shared hosting: upload the binary there. A missing execute bit is fixed automatically when PHP may `chmod`.
4. `/usr/local/bin`, `/usr/bin`, `/opt/eduplus` and `PATH`

Old EduplusQR binaries (from SDK 1.0.1) are skipped automatically. Check what's used:

```php
echo QrCode::client()->binary(), ' ', QrCode::version();   // /usr/local/bin/EduplusQR-linux-amd64 1.0.0
```

PHP needs `proc_open()`, which is enabled by default.

## Single codes

```php
use Mojahed\EduplusQR\QrCode;
use Mojahed\EduplusQR\BarCode;

QrCode::make('https://eduplus-bd.com');        // QR code
BarCode::make('STU-2025-0142');                // Code 128 (default)
BarCode::make('590123412345', 'ean13');        // check digit added automatically
BarCode::make('STU-2025-0142', 'datamatrix');  // other 2D types too
```

Supported types (`QrCode::types()`): `qr`, `code128`, `gs1-128`, `code39`, `code93`, `codabar`, `itf`, `itf14`, `2of5`, `ean13`, `ean8`, `upca`, `datamatrix`, `pdf417`, `aztec`.

## Getting the output

| Method | Returns | Writes a file |
|---|---|---|
| `save($path)` | `Result` (path, width, height, sha256, …) | yes (`.png`, `.svg` or `.pdf`; folders are created) |
| `toPng()`, `toSvg()`, `toPdf()`, `toString($format)` | raw bytes | no |
| `toBase64($format = null)` | base64 string | no |
| `toDataUri($format = null)` | `data:image/png;base64,...` | no |
| `generateInMemory($format = null)` | `Result` with `base64()`, `dataUri()`, `contents()` | no |
| `send($format = null, $downloadName = null)` | sends headers and the image to the browser | no |
| `validate()` / `isValid()` | `Result` / `bool` | no (checks only) |

```php
$result = QrCode::make($url)->save(__DIR__ . "/storage/certs/{$id}.png");
$result->path();  $result->width();  $result->qrVersion();  $result->sha256();

echo '<img src="' . BarCode::make($roll)->showText()->toDataUri() . '">';

QrCode::make($url)->send();                    // inline PNG
QrCode::make($url)->send('pdf', 'qr.pdf');     // download
```

Use absolute paths with `save()` (for example `__DIR__ . '/...'`).

## Options

Every method returns the code, so you can chain them:

```php
QrCode::make($url)
    ->ecc('H')                     // L, M, Q, H; raised automatically when it fits
    ->module(6)                    // pixels per module
    ->style('rounded')             // square, dots, rounded
    ->logo(__DIR__ . '/logo.png', 0.22)   // always verified
    ->color('#0d47a1', '#ffffff')
    ->label('Scan to verify')
    ->save($path);
```

| Method | Purpose |
|---|---|
| `type($t)` | Code type |
| `ecc($level)` | QR `L/M/Q/H`, PDF417 `0-8`, Aztec `5-95` (%) |
| `module($px)` / `xdim($mm)` / `dpi($dpi)` | Module size in pixels, or in millimetres for print |
| `size($w, $h = null)`, `width($px)`, `height($px)` | Exact image size; the module size is fitted to it |
| `quiet($modules)`, `margin($px)` | Quiet zone (0 removes it) and extra padding |
| `bare()` | No quiet zone, no padding, transparent background, no text |
| `showText()`, `hideText()`, `label($text)`, `fontSize($px)` | Human-readable text or a custom caption |
| `color($fg, $bg)`, `foreground()`, `background()`, `transparent()` | Colours (`#RRGGBB`, `#RRGGBBAA`, a name, `transparent`) |
| `rotate($deg)` | 0 / 90 / 180 / 270 |
| `format($f)` | `png`, `svg`, `pdf` |
| `qrVersion($v)`, `qrMinVersion($v)`, `mask($m)`, `eci()`, `exactEcc()` | QR controls (a fixed version gives every code the same size) |
| `style($s)`, `logo($path, $size)`, `logoSize($f)` | QR styling |
| `checksum()`, `fullAscii()` | Code 39 / ITF options |
| `verify()` | Decode the result and fail if it does not read back |
| `force()` | Allow low-contrast colours |
| `option($name, $value)`, `options([...])` | Any binary option by name |

## Content formats

These build correctly escaped content that phones understand:

```php
QrCode::wifi('Campus', 'secret');                       // auth: wpa (default), wpa3, wep, nopass
QrCode::vcard(['name' => 'Rahim Uddin', 'org' => 'Eduplus', 'phone' => '+8801711000000']);
QrCode::email('office@eduplus-bd.com', 'Admission query');
QrCode::sms('+8801711000000', 'Roll 42');
QrCode::tel('+8801711000000');
QrCode::geo(23.8103, 90.4125);
QrCode::event(['summary' => 'Admission test', 'start' => '2026-01-10 10:00']);
QrCode::emv([                                           // EMVCo payment (Bangla QR and similar)
    'merchant_name' => 'EDUPLUS SCHOOL', 'merchant_city' => 'DHAKA',
    'guid' => '<from your bank>', 'merchant_id' => '<from your bank>', 'amount' => '1500',
]);
QrCode::payload('mecard', ['name' => 'Rahim', 'phone' => '+880...']);
```

## Batches (bulk generation)

A batch generates every code in **one process on all CPU cores**. That is much faster than calling `save()` in a loop. You give each row **your own id**, and the results come back **keyed by that id, in the order you added them**.

```php
$batch = BarCode::batch()->showText()->module(3);      // defaults for every row
foreach ($students as $s) {
    $batch->add($s['id'], $s['roll']);                 // id, text, [per-row options]
}
// or: $batch->addMany(array_column($students, 'roll', 'id'));
```

Then choose where the images go:

```php
// 1. In memory: show them directly, nothing is saved
$results = $batch->toBase64();
foreach ($students as $s) {
    $item = $results[$s['id']];
    echo $item->ok() ? '<img src="' . $item->dataUri() . '">' : 'Error: ' . $item->error();
}

// 2. Files ({id} = your id, made file-name safe; also {t}, {n}, {type}, {shard})
$results = $batch->saveTo(__DIR__ . '/storage/cards/{id}.png');
foreach ($results as $id => $item) {
    if ($item->ok()) { saveBarcodePath($id, $item->path()); }
}

// 3. One ZIP
$batch->toZip(__DIR__ . '/storage/admit-cards.zip', '{id}.png');

// 4. Printable PDF label sheets
BarCode::sequence('LIB-{00001..02400}')->showText()->toSheet(__DIR__ . '/labels.pdf', 'a4', '3x8');

// 5. Validate only
BarCode::batch('ean13')->addMany($gtins)->validate()->errors();   // [id => message]
```

Mixed rows and content formats in one batch:

```php
QrCode::batch()                                  // rows can override the type
    ->add('cert-1', $url, ['style' => 'rounded'])
    ->add('roll-1', 'STU-1', ['type' => 'code128', 'text' => true])
    ->addPayload('lab-wifi', 'wifi', ['ssid' => 'Lab-1', 'password' => 'x'])
    ->addCode('call', QrCode::tel('+8801711000000')->style('dots'))
    ->toBase64();
```

Working with results:

```php
$results->all();  $results->successful();  $results->failed();  $results->errors();
$results->allSuccessful();  $results->summary();  $results->throw();

$item = $results['stu-7'];
$item->ok(); $item->error(); $item->path(); $item->dataUri(); $item->base64(); $item->contents();
```

One bad row never stops a batch; it appears in `failed()`. Other batch settings: `->workers(4)`, `->skipExisting()`, `->manifest(__DIR__ . '/batch.csv')`.

## Errors

```php
use Mojahed\EduplusQR\Exceptions\GenerationException;

try {
    BarCode::make($input, 'ean13')->save($path);
} catch (GenerationException $e) {
    $e->getMessage();       // "ean13: invalid check digit: got 8, expected 7"
    $e->isInvalidInput();   // bad data (show it to the user)
    $e->isIoError();        // file could not be written
    $e->isVerifyFailure();  // image did not decode back
    $e->isTimeout();
}
```

`Mojahed\EduplusQR\Exceptions\BinaryNotFoundException` means the binary is missing, not executable, or too old. Both extend `Mojahed\EduplusQR\Exceptions\EduplusException`.

## Configuration

Call `configure()` once at startup. It's optional, because the defaults and auto-detection work without it.

```php
QrCode::configure([
    'binary' => '/usr/local/bin/EduplusQR-linux-amd64',   // omit to auto-detect
    'binary_dir' => __DIR__ . '/bin',                     // extra folder to search
    'timeout' => 120,                                     // seconds per call (0 = no limit)
    'base_dir' => __DIR__ . '/storage',                   // never write outside this folder
    'workers' => null,                                    // batch parallelism (default: CPU cores)
    'defaults' => ['fg' => '#0d47a1'],                    // every code
    'qr_defaults' => ['style' => 'rounded'],              // QR codes only
    'barcode_defaults' => ['module' => 3, 'text' => true],// barcodes only
]);

QrCode::client()->onWarning(function (string $warning) {   // e.g. a very large logo
    error_log('EduplusQR: ' . $warning);
});
```

`QrCode` and `BarCode` share this configuration. For two independent setups in one app, use `new \Mojahed\EduplusQR\Client($binary, $config)`, which has the same methods (`->qr()`, `->barcode()`, `->batch()`, …).

## Upgrading from 1.0.1

| 1.0.1 | 1.0.2 |
|---|---|
| Binaries bundled in `bin/` (QR only) | Install the EduplusQR binary once per server (QR **and** barcodes) |
| `use Mojahed\EduplusQR;` | `use Mojahed\EduplusQR\QrCode;` / `use Mojahed\EduplusQR\BarCode;` |
| `EduplusQR::create()->text($t)->output($p)->generate()` | `QrCode::make($t)->save($p)` |
| `EduplusQR::quick($t, $p, 256)` | `QrCode::make($t)->size(256)->save($p)` |
| `->size(256)` | `->size(256)` (exact image size) or `->module(4)` |
| `->errorCorrection('H')` | `->ecc('H')` |
| `->margin(0)` | `->quiet(0)`; `->margin($px)` is now extra padding |
| `generateAndReturn()` / `generateBase64()` (wrote a file first) | `toPng()` / `toBase64()` (no file) |
| `$qr->errors` array, `false` on failure | `GenerationException` with the exact message |
| Copied binaries into `~/eduplus_qr_bin` when `chmod` was disabled | Put the binary in your project's `bin/` or set `EDUPLUS_QR_BINARY` |

## Examples and tests

```bash
composer install
EDUPLUS_QR_BINARY=/path/to/EduplusQR php examples/basic.php     # writes examples/output/
EDUPLUS_QR_BINARY=/path/to/EduplusQR composer test
```

## License

MIT. See [LICENSE](LICENSE).

## Author

**Md Mojahedul Islam**: dev.mojahedul@gmail.com · https://md-mojahed.github.io

Made With (❤️ Love) By: md-mojahed.github.io
