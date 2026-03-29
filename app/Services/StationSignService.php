<?php

namespace App\Services;

use App\Models\Station;
use RuntimeException;
use Symfony\Component\Process\Process;

class StationSignService
{
    public function generate(Station $station): string
    {
        $tempDir = sys_get_temp_dir().'/station-sign-'.uniqid();
        mkdir($tempDir, 0755, true);

        try {
            return $this->compile($station, $tempDir);
        } finally {
            $this->cleanup($tempDir);
        }
    }

    private function compile(Station $station, string $tempDir): string
    {
        $urls = $this->buildUrls($station->token);

        $this->generateQrCode($urls['cash-full'], $tempDir.'/qr-cash-full.svg');
        $this->generateQrCode($urls['change-request'], $tempDir.'/qr-change-request.svg');
        $this->generateQrCode($urls['other'], $tempDir.'/qr-other.svg');

        $outputPath = $tempDir.'/station-sign.pdf';

        $args = [
            'typst', 'compile',
            '--root', '/',
            '--font-path', resource_path('fonts/public-sans'),
            '--input', 'station-name='.$station->name,
            '--input', 'station-location='.($station->location ?? ''),
            '--input', 'station-token='.$station->token,
            '--input', 'logo-path='.public_path('logos/Horizontal - Light.svg'),
            '--input', 'qr-cash-full='.$tempDir.'/qr-cash-full.svg',
            '--input', 'qr-change-request='.$tempDir.'/qr-change-request.svg',
            '--input', 'qr-other='.$tempDir.'/qr-other.svg',
            '--input', 'url-cash-full='.$urls['cash-full'],
            '--input', 'url-change-request='.$urls['change-request'],
            '--input', 'url-other='.$urls['other'],
        ];

        $photoPath = storage_path('app/station-sign/photo.jpg');
        if (is_file($photoPath)) {
            $args[] = '--input';
            $args[] = 'photo-path='.$photoPath;
        }

        $args[] = resource_path('typst/station-sign.typ');
        $args[] = $outputPath;

        $process = new Process($args);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Typst compilation failed: '.$process->getErrorOutput());
        }

        return file_get_contents($outputPath);
    }

    private function buildUrls(string $token): array
    {
        return [
            'cash-full' => 'https://'.config('services.domains.cash_full').'/s/'.$token,
            'change-request' => 'https://'.config('services.domains.change_request').'/s/'.$token,
            'other' => 'https://'.config('services.domains.other').'/s/'.$token,
        ];
    }

    private function generateQrCode(string $url, string $outputPath): void
    {
        $process = new Process([
            'qrencode', '-l', 'H', '-t', 'SVG', '-s', '10',
            '--background', 'f6f5f4',
            '-o', $outputPath, $url,
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('QR code generation failed: '.$process->getErrorOutput());
        }
    }

    private function cleanup(string $tempDir): void
    {
        foreach (glob($tempDir.'/*') as $file) {
            unlink($file);
        }
        rmdir($tempDir);
    }
}
