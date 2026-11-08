<?php

namespace Mojahed\EduplusQR\Tests;

use Mojahed\EduplusQR\BatchItem;
use Mojahed\EduplusQR\BatchResult;
use Mojahed\EduplusQR\Code;
use Mojahed\EduplusQR\Client;
use Mojahed\EduplusQR\Exceptions\BinaryNotFoundException;
use Mojahed\EduplusQR\Exceptions\EduplusException;
use Mojahed\EduplusQR\Exceptions\GenerationException;
use Mojahed\EduplusQR\Result;

/**
 * Tests that need no binary.
 */
class OptionsTest extends TestCase
{
    private function code(): Code
    {
        return new Code(new Client('/nonexistent'));
    }

    public function testArgumentsAreBuiltSafely(): void
    {
        $args = $this->code()->text('a "b" $(rm -rf /); c')
            ->type('code128')->module(3)->showText()->hideText()->label('Roll 42')
            ->logoSize(0.22)->xdim(0.33)->color('#000', 'transparent')
            ->toArguments();

        $this->assertSame([
            '-t', 'a "b" $(rm -rf /); c',
            '-type', 'code128', '-module', '3', '-text=false', '-label', 'Roll 42',
            '-logo-size', '0.22', '-xdim', '0.33', '-fg', '#000', '-bg', 'transparent',
        ], $args);
    }

    public function testBareAndSize(): void
    {
        $args = $this->code()->text('x')->size(300)->bare()->toArguments();
        $this->assertSame(['-t', 'x', '-w', '300', '-height', '300', '-quiet', '0', '-margin', '0', '-bg', 'transparent', '-text=false'], $args);
    }

    public function testPayloadArguments(): void
    {
        $args = $this->code()->payload('wifi', ['ssid' => 'Lab', 'password' => 'a;b', 'hidden' => true, 'auth' => null])->toArguments();
        $this->assertSame(['-payload', 'wifi', '-p', 'ssid=Lab', '-p', 'password=a;b', '-p', 'hidden=true'], $args);
        $row = $this->code()->payload('tel', ['number' => '+880'])->toRow();
        $this->assertSame('tel', $row['payload']);
        $this->assertEquals((object) ['number' => '+880'], $row['p']);
    }

    public function testOptionValidation(): void
    {
        $this->expectException(EduplusException::class);
        $this->code()->option('o', '/etc/passwd');
    }

    public function testInvalidOptionName(): void
    {
        $this->expectException(EduplusException::class);
        $this->code()->option('bad name', 1);
    }

    public function testNullRemovesOption(): void
    {
        $c = $this->code()->text('x')->module(3)->option('module', null);
        $this->assertSame(['-t', 'x'], $c->toArguments());
    }

    public function testMissingText(): void
    {
        $this->expectException(GenerationException::class);
        $this->code()->toArguments();
    }

    public function testDefaultsPerType(): void
    {
        $client = new Client('/nonexistent', [
            'defaults' => ['fg' => '#111111'],
            'qr_defaults' => ['style' => 'rounded'],
            'barcode_defaults' => ['text' => true],
        ]);
        $this->assertSame(['-t', 'x', '-fg', '#111111', '-style', 'rounded', '-type', 'qr'], $client->qr('x')->toArguments());
        $this->assertSame(['-t', 'x', '-fg', '#111111', '-text=true', '-type', 'ean13'], $client->barcode('x', 'ean13')->toArguments());
    }

    public function testBinaryNotFound(): void
    {
        $this->expectException(BinaryNotFoundException::class);
        (new Client($this->path('missing')))->binary();
    }

    public function testPlatformBinaryName(): void
    {
        $this->assertMatchesRegularExpression('/^EduplusQR-(linux|darwin|windows)-[a-z0-9]+(\.exe)?$/', Client::platformBinaryName());
    }

    private function fakeBinary(string $dir, string $script, int $mode = 0755): string
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Shell-script fake binaries need a Unix shell');
        }
        @mkdir($dir, 0777, true);
        $file = $dir . DIRECTORY_SEPARATOR . Client::platformBinaryName();
        file_put_contents($file, "#!/bin/sh\n" . $script . "\n");
        chmod($file, $mode);
        return $file;
    }

    public function testDetectBinaryInDirectory(): void
    {
        $file = $this->fakeBinary($this->path('ok'), 'echo "EduplusQR 1.0.0 (test)"');
        $this->assertSame($file, Client::detectBinary([$this->path('ok')]));
    }

    public function testDetectionFixesMissingExecuteBit(): void
    {
        $file = $this->fakeBinary($this->path('upload'), 'echo "EduplusQR 1.0.0 (test)"', 0644);
        $this->assertSame($file, Client::detectBinary([$this->path('upload')]));
        $this->assertTrue(is_executable($file));
    }

    public function testDetectionSkipsLegacyBinaries(): void
    {
        $legacy = 'echo "flag provided but not defined: -version" >&2; exit 2';
        $this->fakeBinary($this->path('old'), $legacy);
        $good = $this->fakeBinary($this->path('new'), 'echo "EduplusQR 1.2.3"');
        $this->assertFalse(Client::isCurrentBinary($this->path('old') . DIRECTORY_SEPARATOR . Client::platformBinaryName()));
        $this->assertSame($good, Client::detectBinary([$this->path('old'), $this->path('new')]));
    }

    public function testConfiguredLegacyBinaryGivesClearError(): void
    {
        $file = $this->fakeBinary($this->path('legacy'), 'echo "flag provided but not defined: -json" >&2; exit 2');
        $this->expectException(BinaryNotFoundException::class);
        $this->expectExceptionMessage('older EduplusQR release');
        (new Client($file))->qr('x')->save($this->path('x.png'));
    }

    public function testResultAccessors(): void
    {
        $r = new Result(['id' => 'a', 'output' => '/x.png', 'type' => 'qr', 'content' => 'hi', 'format' => 'svg',
            'width' => 10, 'height' => 12, 'module' => 2, 'qr' => ['version' => 3, 'ecc' => 'Q', 'mask' => 5],
            'bytes' => 99, 'sha256' => 'abc', 'verified' => true, 'base64' => base64_encode('<svg/>')]);
        $this->assertSame('a', $r->id());
        $this->assertSame('/x.png', $r->path());
        $this->assertSame(3, $r->qrVersion());
        $this->assertSame('Q', $r->ecc());
        $this->assertTrue($r->verified());
        $this->assertSame('<svg/>', $r->contents());
        $this->assertSame('data:image/svg+xml;base64,' . base64_encode('<svg/>'), $r->dataUri());
        $this->assertNull($r->warning());
    }

    public function testBatchResultCollection(): void
    {
        $items = [
            'b' => new BatchItem('b', 1, BatchItem::OK, new Result(['id' => 'b'])),
            'a' => new BatchItem('a', 2, BatchItem::ERROR, null, 'bad'),
        ];
        $res = new BatchResult($items, [], ['total' => 2, 'ok' => 1, 'failed' => 1, 'skipped' => 0]);
        $this->assertCount(2, $res);
        $this->assertSame(['b', 'a'], array_keys(iterator_to_array($res)));
        $this->assertTrue($res['b']->ok());
        $this->assertSame(['a' => 'bad'], $res->errors());
        $this->assertFalse($res->allSuccessful());
        $this->assertNull($res['zzz']);
        $this->expectException(GenerationException::class);
        $res->throw();
    }

    public function testBinaryFromEnvironment(): void
    {
        putenv('EDUPLUS_QR_BINARY=' . $this->path('from-env'));
        try {
            $client = new Client();
            $this->expectException(BinaryNotFoundException::class);
            $this->expectExceptionMessage($this->path('from-env'));
            $client->binary();
        } finally {
            putenv('EDUPLUS_QR_BINARY');
        }
    }

    public function testMakesUploadedBinaryExecutable(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('No execute bit on Windows');
        }
        $file = $this->path('EduplusQR');
        file_put_contents($file, "#!/bin/sh\n");
        chmod($file, 0644);
        $this->assertSame($file, (new Client($file))->binary());
        $this->assertTrue(is_executable($file));
    }
}
