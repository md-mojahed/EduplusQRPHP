<?php

require_once __DIR__.'/../src/EduplusQR.php';

use Mojahed\EduplusQR;

// Example 1: Basic usage
echo "Example 1: Basic QR Code\n";
$qr = EduplusQR::create()
    ->text("Hello World from EduplusQR!")
    ->output("output/basic.png")
    ->generate();

if ($qr) {
    echo "✓ QR code generated: output/basic.png\n\n";
} else {
    echo "✗ Failed to generate QR code\n";
    print_r(EduplusQR::create()->errors);
}

// Example 2: JSON data with custom settings
echo "Example 2: JSON QR Code with custom settings\n";
$jsonData = json_encode([
    'user_id' => 12345,
    'name' => 'Md Mojahed',
    'email' => 'dev.mojahedul@gmail.com',
    'timestamp' => time()
]);

$qr2 = EduplusQR::create()
    ->text($jsonData)
    ->output("output/json_qr.png")
    ->size(512)
    ->errorCorrection('H')
    ->generate();

if ($qr2) {
    echo "✓ JSON QR code generated: output/json_qr.png\n\n";
} else {
    echo "✗ Failed to generate JSON QR code\n";
}

// Example 3: Quick method
echo "Example 3: Quick generation\n";
$result = EduplusQR::quick("https://eduplus-bd.com", "output/url_qr.png", 300);

if ($result) {
    echo "✓ URL QR code generated: output/url_qr.png\n\n";
} else {
    echo "✗ Failed to generate URL QR code\n";
}

// Example 4: Generate and get base64
echo "Example 4: Generate and return base64\n";
$base64 = EduplusQR::create()
    ->text("Base64 QR Code")
    ->output("output/base64_qr.png")
    ->size(256)
    ->generateBase64();

if ($base64) {
    echo "✓ Base64 QR code generated (length: " . strlen($base64) . " chars)\n";
    echo "Data URI: data:image/png;base64," . substr($base64, 0, 50) . "...\n\n";
} else {
    echo "✗ Failed to generate base64 QR code\n";
}

// Example 5: Payment QR Code
echo "Example 5: Payment QR Code\n";
$paymentData = json_encode([
    'merchant_id' => 'MERCH123',
    'amount' => 1500.50,
    'currency' => 'BDT',
    'invoice' => 'INV-' . uniqid(),
    'customer' => [
        'name' => 'John Doe',
        'phone' => '01800000000'
    ]
]);

$payment = EduplusQR::create()
    ->text($paymentData)
    ->output("output/payment_qr.png")
    ->size(400)
    ->errorCorrection('H')
    ->margin(0)
    ->generate();

if ($payment) {
    echo "✓ Payment QR code generated: output/payment_qr.png\n";
} else {
    echo "✗ Failed to generate payment QR code\n";
}
