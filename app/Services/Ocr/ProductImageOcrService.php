<?php

namespace App\Services\Ocr;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProductImageOcrService
{
    /**
     * Patterns and keywords that identify nutritional, legal, ingredient, or promotional noise.
     *
     * @var array<int, string>
     */
    protected array $blacklistedPatterns = [
        'nutrition',
        'serving size',
        'servings',
        'calories',
        'daily value',
        'total fat',
        'saturated fat',
        'trans fat',
        'cholesterol',
        'sodium',
        'total carb',
        'dietary fiber',
        'sugars',
        'protein',
        'vitamin',
        'calcium',
        'iron',
        'potassium',
        'ingredients',
        'contains:',
        'manufactured by',
        'manufactured for',
        'distributed by',
        'imported by',
        'product of',
        'keep refrigerated',
        'store in a cool',
        'store in cool',
        'shake well',
        'best before',
        'best by',
        'expiry',
        'exp date',
        'mfg date',
        'batch no',
        'lot no',
        'recycle',
        'scan here',
        'qr code',
        'barcode',
        'barcode number',
        'customer care',
        'www.',
        'http:',
        'https:',
        '.com',
        '.org',
        '.net',
        '@',
    ];

    /**
     * Execute OCR on the given image and extract product name candidates.
     *
     * @return array{
     *     success: bool,
     *     product_name: ?string,
     *     extracted_text: array<int, string>,
     *     raw_text: string,
     *     message?: string
     * }
     */
    public function recognize(UploadedFile|string $image): array
    {
        $cleanupNeeded = false;
        $filePath = null;

        try {
            if ($image instanceof UploadedFile) {
                $filePath = $image->getRealPath();
                if (! $filePath || ! file_exists($filePath)) {
                    $filePath = tempnam(sys_get_temp_dir(), 'vpos_ocr_');
                    $image->move(dirname($filePath), basename($filePath));
                    $cleanupNeeded = true;
                }
            } else {
                $filePath = $image;
            }

            if (! $filePath || ! file_exists($filePath)) {
                return [
                    'success' => false,
                    'product_name' => null,
                    'extracted_text' => [],
                    'raw_text' => '',
                    'message' => 'Image file could not be read or does not exist.',
                ];
            }

            $rawText = $this->runTesseract($filePath);

            if (trim($rawText) === '') {
                return [
                    'success' => true,
                    'product_name' => null,
                    'extracted_text' => [],
                    'raw_text' => '',
                    'message' => 'No readable text was detected in this image.',
                ];
            }

            $candidates = $this->extractCandidates($rawText);
            $bestName = $this->selectBestProductName($candidates);

            return [
                'success' => true,
                'product_name' => $bestName,
                'extracted_text' => array_values(array_unique($candidates)),
                'raw_text' => $rawText,
            ];
        } catch (\Throwable $e) {
            Log::warning('OCR recognition error: '.$e->getMessage(), ['exception' => $e]);

            return [
                'success' => false,
                'product_name' => null,
                'extracted_text' => [],
                'raw_text' => '',
                'message' => 'OCR recognition failed: '.$e->getMessage(),
            ];
        } finally {
            if ($cleanupNeeded && $filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    /**
     * Execute the Tesseract binary safely via Symfony Process without shell expansion.
     */
    protected function runTesseract(string $imagePath): string
    {
        $binary = (string) config('services.tesseract.path', '/usr/bin/tesseract');
        $timeout = (int) config('services.tesseract.timeout', 15);
        $language = (string) config('services.tesseract.language', 'eng');

        if (! is_executable($binary)) {
            $systemPath = trim((string) @shell_exec('which tesseract 2>/dev/null'));
            if ($systemPath !== '' && is_executable($systemPath)) {
                $binary = $systemPath;
            } else {
                throw new \RuntimeException("Tesseract executable not found at [{$binary}]. Please ensure tesseract-ocr is installed.");
            }
        }

        // Arguments are passed as an array, completely immune to shell command injection
        // Outputting to stdout directly avoids writing temporary output files to disk
        $process = new Process([
            $binary,
            $imagePath,
            'stdout',
            '-l',
            $language,
            '--oem',
            '1',
            '--psm',
            '3',
        ]);

        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * Extract and sanitize plausible product name lines from raw OCR output.
     *
     * @return array<int, string>
     */
    public function extractCandidates(string $rawText): array
    {
        $lines = preg_split('/[\r\n]+/', $rawText) ?: [];
        $candidates = [];

        foreach ($lines as $line) {
            $cleaned = $this->cleanLine($line);

            if ($cleaned === '' || mb_strlen($cleaned) < 2) {
                continue;
            }

            if ($this->isBlacklisted($cleaned)) {
                continue;
            }

            $candidates[] = $cleaned;
        }

        return $candidates;
    }

    /**
     * Clean up an OCR text line from surrounding noise characters.
     */
    protected function cleanLine(string $line): string
    {
        $line = trim($line, " \t\n\r\0\x0B|~_`^=*-—#§©®™");
        $line = preg_replace('/\s+/', ' ', $line);

        return trim($line ?? '');
    }

    /**
     * Determine if a line is noise, nutrition facts, barcode numbers, or legal metadata.
     */
    public function isBlacklisted(string $line): bool
    {
        $lower = mb_strtolower($line);

        // 1. Blacklisted keyword matches
        foreach ($this->blacklistedPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        // 2. Purely numeric or punctuation (e.g. barcode digits, timestamps, prices)
        if (preg_match('/^[\d\s\.\,\-\:\/\\\#]+$/', $line)) {
            return true;
        }

        // 3. Standalone volume / weight / measurement only (e.g. "500ml", "1.5 L", "330 ml", "100g")
        if (preg_match('/^(\d+(\.\d+)?\s*(ml|l|cl|oz|fl\s*oz|g|kg|mg|lb|pack|can|bottle|pcs))$/i', $line)) {
            return true;
        }

        // 4. Mostly non-alphanumeric noise (e.g. ";;; / /")
        $lettersAndDigits = preg_replace('/[^\p{L}\p{N}]/u', '', $line);
        if (mb_strlen($lettersAndDigits) < 2) {
            return true;
        }

        return false;
    }

    /**
     * Score candidate lines and select the most likely product name.
     *
     * @param  array<int, string>  $candidates
     */
    public function selectBestProductName(array $candidates): ?string
    {
        if (empty($candidates)) {
            return null;
        }

        $scored = [];

        foreach ($candidates as $index => $candidate) {
            $score = $this->scoreCandidate($candidate, $index);
            $scored[] = [
                'text' => $candidate,
                'score' => $score,
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $scored[0]['text'] ?? null;
    }

    /**
     * Calculate a heuristic relevance score for a candidate line.
     */
    protected function scoreCandidate(string $text, int $lineIndex): float
    {
        $score = 100.0;

        // Position bonus: Brand and product names are almost always at the top of the label
        $score -= ($lineIndex * 15.0);

        $len = mb_strlen($text);
        $wordCount = str_word_count($text);

        // Ideal product name length: 4 to 35 characters
        if ($len >= 4 && $len <= 35) {
            $score += 25.0;
        } elseif ($len > 50) {
            $score -= 30.0;
        }

        // Ideal word count: 1 to 4 words
        if ($wordCount >= 1 && $wordCount <= 4) {
            $score += 20.0;
        } elseif ($wordCount > 6) {
            $score -= 25.0;
        }

        // Capitalization bonuses (All Caps or Title Case, including hyphens and apostrophes)
        if ($text === mb_strtoupper($text) && preg_match('/[A-Z]/', $text)) {
            $score += 15.0;
        } elseif (preg_match('/^[A-Z][a-zA-Z0-9\-\'\&]+(\s+[A-Z][a-zA-Z0-9\-\'\&]+)*$/', $text)) {
            $score += 20.0;
        }

        // Bonus for attached volume or type descriptor (e.g. "Coca-Cola 500ml", "Angkor Beer Can")
        if (preg_match('/\b\d+(\.\d+)?\s*(ml|l|cl|g|kg|can|bottle)\b/i', $text)) {
            $score += 10.0;
        }

        return $score;
    }
}
