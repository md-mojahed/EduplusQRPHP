# EduplusQR PHP SDK

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

EduplusQR is a lightweight, high-performance PHP SDK for generating QR codes using a fast Go binary backend. It generates PNG QR codes from plain text or JSON-encoded strings with zero external PHP dependencies.

------------------------------------------------------------
## Features
------------------------------------------------------------

- **Lightning Fast**: Powered by optimized Go binary
- **Zero PHP Dependencies**: No GD, ImageMagick, or other extensions required
- **Cross-Platform**: Works on Linux (amd64/arm64), macOS (amd64/arm64), and Windows
- **Fluent API**: Clean, chainable method syntax
- **Flexible Output**: Save to file, return binary data, or get base64 encoded
- **Customizable**: Configure size, error correction level, and margins
- **JSON Support**: Perfect for encoding structured data

------------------------------------------------------------
## Installation
------------------------------------------------------------

Install via Composer:
```bash
composer require mojahed/eduplusqr
```

------------------------------------------------------------
## Quick Start
------------------------------------------------------------

```php
<?php
use Mojahed\EduplusQR;

// Simple usage
EduplusQR::quick("Hello World", "qr.png", 256);

// Fluent API
EduplusQR::create()
    ->text("https://eduplus-bd.com")
    ->output("qr.png")
    ->size(512)
    ->errorCorrection('H')
    ->generate();
```

------------------------------------------------------------
## Usage Examples
------------------------------------------------------------

### 1. Basic QR Code Generation
```php
use Mojahed\EduplusQR;

$qr = EduplusQR::create()
    ->text("Hello World from EduplusQR!")
    ->output("output/qr.png")
    ->generate();

if ($qr) {
    echo "QR code generated successfully!";
} else {
    print_r(EduplusQR::create()->errors);
}
```

### 2. JSON Data QR Code
```php
$data = json_encode([
    'user_id' => 12345,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'timestamp' => time()
]);

EduplusQR::create()
    ->text($data)
    ->output("user_qr.png")
    ->size(512)
    ->errorCorrection('H')
    ->generate();
```

### 3. Payment QR Code
```php
$paymentData = json_encode([
    'merchant_id' => 'MERCH123',
    'amount' => 1500.50,
    'currency' => 'BDT',
    'invoice' => 'INV-' . uniqid()
]);

EduplusQR::create()
    ->text($paymentData)
    ->output("payment.png")
    ->size(400)
    ->errorCorrection('H')
    ->generate();
```

### 4. Get Base64 Encoded QR Code
```php
$base64 = EduplusQR::create()
    ->text("Base64 QR Code")
    ->output("temp_qr.png")
    ->generateBase64();

// Use in HTML
echo '<img src="data:image/png;base64,' . $base64 . '" />';
```

### 5. Get Binary Data
```php
$binaryData = EduplusQR::create()
    ->text("Binary QR Code")
    ->output("temp_qr.png")
    ->generateAndReturn();

// Send as HTTP response
header('Content-Type: image/png');
echo $binaryData;
```

------------------------------------------------------------
## API Methods
------------------------------------------------------------

### `create()`
Create a new EduplusQR instance.

### `text($text)`
Set the text or JSON data to encode in the QR code.

### `output($path)`
Set the output file path. Directory will be created automatically if it doesn't exist.

### `size($pixels)`
Set the QR code size in pixels (default: 256).

### `errorCorrection($level)`
Set error correction level: 'L' (Low), 'M' (Medium), 'Q' (Quartile), 'H' (High). Default: 'M'.

### `margin($pixels)`
Set border margin in pixels (default: 0 for no margin).

### `generate()`
Generate the QR code and save to file. Returns `true` on success, `false` on failure.

### `generateAndReturn()`
Generate the QR code and return binary PNG data.

### `generateBase64()`
Generate the QR code and return base64 encoded string.

### `quick($text, $output, $size = 256)`
Static method for quick QR code generation.

### `$errors`
Array containing error messages from the last operation.

------------------------------------------------------------
## Configuration Options
------------------------------------------------------------

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| text | string | required | Text or JSON to encode |
| output | string | required | Output file path |
| size | int | 256 | QR code size in pixels |
| errorCorrection | string | 'M' | Error correction: L, M, Q, H |
| margin | int | 0 | Border margin in pixels |

------------------------------------------------------------
## Error Correction Levels
------------------------------------------------------------

- **L (Low)**: ~7% error recovery
- **M (Medium)**: ~15% error recovery (default)
- **Q (Quartile)**: ~25% error recovery
- **H (High)**: ~30% error recovery

Higher levels create more complex QR codes but are more resilient to damage.

------------------------------------------------------------
## Requirements
------------------------------------------------------------

- PHP 7.4 or higher
- Linux (amd64/arm64), macOS (amd64/arm64), or Windows (amd64)
- No PHP extensions required

------------------------------------------------------------
## Platform Support
------------------------------------------------------------

The package automatically detects your platform and uses the appropriate binary:
- Linux x86_64 (amd64)
- Linux ARM64 (aarch64)
- macOS Intel (amd64)
- macOS Apple Silicon (arm64)
- Windows x86_64 (amd64)

------------------------------------------------------------
## Performance
------------------------------------------------------------

EduplusQR is optimized for high-volume generation:
- Generates QR codes in milliseconds
- Minimal memory footprint
- No PHP extension dependencies
- Production-ready for server environments

------------------------------------------------------------
## Author
------------------------------------------------------------

**Md Mojahedul Islam**
- Email: dev.mojahedul@gmail.com
- Website: https://md-mojahed.github.io

------------------------------------------------------------
## License
------------------------------------------------------------

MIT License - see LICENSE file for details

------------------------------------------------------------
## Contributing
------------------------------------------------------------

Contributions are welcome! Please feel free to submit a Pull Request.
