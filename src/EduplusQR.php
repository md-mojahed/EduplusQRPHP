<?php

namespace Mojahed;

class EduplusQR
{
    protected $binaryPath = null;
    protected $text = null;
    protected $output = null;
    protected $size = 256;
    protected $errorCorrection = 'M';
    protected $margin = 0;
    public $errors = [];

    public function __construct()
    {
        $this->detectBinary();
    }

    protected function detectBinary()
    {
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');
        
        // Determine binary name based on OS and architecture
        $binaryName = $this->getBinaryName($os, $arch);
        if (!$binaryName) {
            $this->errors[] = "Unsupported platform: {$os} ({$arch})";
            return false;
        }
        
        $packageBinDir = __DIR__ . '/../bin/';
        $packageBinaryPath = $packageBinDir . $binaryName;
        
        // Check if chmod is available
        $chmodAvailable = $this->isFunctionEnabled('chmod');
        
        // Try package binary first
        if (file_exists($packageBinaryPath)) {
            if ($os !== 'Windows' && $chmodAvailable) {
                @chmod($packageBinaryPath, 0755);
            }
            
            // Test if binary is executable
            if ($this->isBinaryExecutable($packageBinaryPath)) {
                $this->binaryPath = $packageBinaryPath;
                return true;
            }
        }
        
        // If package binary not executable (chmod disabled), try home directory
        $homeDir = $this->getHomeDirectory();
        $homeBinDir = $homeDir . '/eduplus_qr_bin/';
        $homeBinaryPath = $homeBinDir . $binaryName;
        
        // Check if binary exists in home directory
        if (file_exists($homeBinaryPath) && $this->isBinaryExecutable($homeBinaryPath)) {
            $this->binaryPath = $homeBinaryPath;
            return true;
        }
        
        // Try to copy binary to home directory
        if ($this->copyBinaryToHome($packageBinaryPath, $homeBinDir, $binaryName)) {
            $this->binaryPath = $homeBinaryPath;
            return true;
        }
        
        $this->errors[] = "Binary not found or not executable for your platform: {$os} ({$arch})";
        return false;
    }
    
    protected function getBinaryName($os, $arch)
    {
        if ($os === 'Linux') {
            if (strpos($arch, 'aarch64') !== false || strpos($arch, 'arm64') !== false) {
                return 'EduplusQR-linux-arm64';
            } else {
                return 'EduplusQR-linux-amd64';
            }
        } elseif ($os === 'Darwin') {
            if (strpos($arch, 'arm64') !== false) {
                return 'EduplusQR-darwin-arm64';
            } else {
                return 'EduplusQR-darwin-amd64';
            }
        } elseif ($os === 'Windows') {
            return 'EduplusQR-windows-amd64.exe';
        }
        
        return null;
    }
    
    protected function getHomeDirectory()
    {
        // Try multiple methods to get home directory
        if (!empty($_SERVER['HOME'])) {
            return $_SERVER['HOME'];
        }
        
        if (!empty($_ENV['HOME'])) {
            return $_ENV['HOME'];
        }
        
        if ($this->isFunctionEnabled('posix_getpwuid') && $this->isFunctionEnabled('posix_getuid')) {
            $userInfo = posix_getpwuid(posix_getuid());
            if (isset($userInfo['dir'])) {
                return $userInfo['dir'];
            }
        }
        
        // Fallback for Windows
        if (PHP_OS_FAMILY === 'Windows') {
            if (!empty($_SERVER['USERPROFILE'])) {
                return $_SERVER['USERPROFILE'];
            }
            if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
                return $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            }
        }
        
        // Last resort
        return '/tmp';
    }
    
    protected function isFunctionEnabled($functionName)
    {
        if (!function_exists($functionName)) {
            return false;
        }
        
        $disabled = explode(',', ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);
        
        return !in_array($functionName, $disabled);
    }
    
    protected function isBinaryExecutable($binaryPath)
    {
        if (!file_exists($binaryPath)) {
            return false;
        }
        
        // Check if is_executable is available
        if ($this->isFunctionEnabled('is_executable')) {
            return is_executable($binaryPath);
        }
        
        // Fallback: try to execute with -h flag
        if ($this->isFunctionEnabled('exec')) {
            $testCommand = escapeshellarg($binaryPath) . ' -h 2>&1';
            @exec($testCommand, $output, $returnCode);
            // If it returns 0 or shows help, it's executable
            return ($returnCode === 0 || !empty($output));
        }
        
        return false;
    }
    
    protected function copyBinaryToHome($sourcePath, $destDir, $binaryName)
    {
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        // Check if required functions are available
        if (!$this->isFunctionEnabled('copy')) {
            return false;
        }
        
        // Create directory if it doesn't exist
        if (!is_dir($destDir)) {
            if ($this->isFunctionEnabled('mkdir')) {
                if (!@mkdir($destDir, 0755, true)) {
                    return false;
                }
            } else {
                return false;
            }
        }
        
        $destPath = $destDir . $binaryName;
        
        // Copy binary
        if (!@copy($sourcePath, $destPath)) {
            return false;
        }
        
        // Try to make it executable
        if (PHP_OS_FAMILY !== 'Windows' && $this->isFunctionEnabled('chmod')) {
            @chmod($destPath, 0755);
        }
        
        return $this->isBinaryExecutable($destPath);
    }

    public static function create()
    {
        return new EduplusQR();
    }

    public function text($text)
    {
        $this->text = $text;
        return $this;
    }

    public function output($output)
    {
        $this->output = $output;
        return $this;
    }

    public function size($size)
    {
        $this->size = (int)$size;
        return $this;
    }

    public function errorCorrection($level)
    {
        if (!in_array($level, ['L', 'M', 'Q', 'H'])) {
            $this->errors[] = "Invalid error correction level: {$level}. Use L, M, Q, or H.";
            return $this;
        }
        $this->errorCorrection = $level;
        return $this;
    }

    public function margin($margin)
    {
        $this->margin = (int)$margin;
        return $this;
    }

    public function generate()
    {
        if (empty($this->text)) {
            $this->errors[] = "Text is required";
            return false;
        }

        if (empty($this->output)) {
            $this->errors[] = "Output path is required";
            return false;
        }

        if (!$this->binaryPath || !file_exists($this->binaryPath)) {
            $this->errors[] = "Binary not available";
            return false;
        }

        $escapedText = escapeshellarg($this->text);
        $escapedOutput = escapeshellarg($this->output);
        
        $command = sprintf(
            '%s -t %s -o %s -size %d -ec %s -margin %d 2>&1',
            escapeshellarg($this->binaryPath),
            $escapedText,
            $escapedOutput,
            $this->size,
            $this->errorCorrection,
            $this->margin
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->errors[] = implode("\n", $output);
            return false;
        }

        if (!file_exists($this->output)) {
            $this->errors[] = "QR code generation failed";
            return false;
        }

        return true;
    }

    public function generateAndReturn()
    {
        if ($this->generate()) {
            return file_get_contents($this->output);
        }
        return null;
    }

    public function generateBase64()
    {
        if ($this->generate()) {
            return base64_encode(file_get_contents($this->output));
        }
        return null;
    }

    public static function quick($text, $output, $size = 256)
    {
        return self::create()
            ->text($text)
            ->output($output)
            ->size($size)
            ->generate();
    }
}
