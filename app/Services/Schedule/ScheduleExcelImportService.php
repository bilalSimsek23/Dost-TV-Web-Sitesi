<?php

namespace App\Services\Schedule;

use App\Models\Program;
use App\Models\Schedule;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ScheduleExcelImportService
{
    public const DAY_MAP = [
        'pazartesi' => 0, 'monday' => 0, 'mon' => 0, '1' => 0,
        'salı' => 1, 'sali' => 1, 'tuesday' => 1, 'tue' => 1, '2' => 1,
        'çarşamba' => 2, 'carsamba' => 2, 'wednesday' => 2, 'wed' => 2, '3' => 2,
        'perşembe' => 3, 'persembe' => 3, 'thursday' => 3, 'thu' => 3, '4' => 3,
        'cuma' => 4, 'friday' => 4, 'fri' => 4, '5' => 4,
        'cumartesi' => 5, 'saturday' => 5, 'sat' => 5, '6' => 5,
        'pazar' => 6, 'sunday' => 6, 'sun' => 6, '7' => 6,
    ];

    public const BROADCAST_TYPES = [
        'normal' => ['is_live' => false, 'is_repeat' => false, 'note' => null],
        'normal yayın' => ['is_live' => false, 'is_repeat' => false, 'note' => null],
        'tekrar' => ['is_live' => false, 'is_repeat' => true, 'note' => null],
        'tekrar yayın' => ['is_live' => false, 'is_repeat' => true, 'note' => null],
        'canlı' => ['is_live' => true, 'is_repeat' => false, 'note' => null],
        'canlı yayın' => ['is_live' => true, 'is_repeat' => false, 'note' => null],
        'canli' => ['is_live' => true, 'is_repeat' => false, 'note' => null],
        'canli yayin' => ['is_live' => true, 'is_repeat' => false, 'note' => null],
        'özel' => ['is_live' => false, 'is_repeat' => false, 'note' => 'Özel Yayın'],
        'özel yayın' => ['is_live' => false, 'is_repeat' => false, 'note' => 'Özel Yayın'],
        'ozel' => ['is_live' => false, 'is_repeat' => false, 'note' => 'Özel Yayın'],
        'ozel yayin' => ['is_live' => false, 'is_repeat' => false, 'note' => 'Özel Yayın'],
    ];

    /**
     * Generates downloadable sample Excel template file.
     */
    public function generateSampleTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Yayın Akışı');

        // Headers
        $headers = ['Gün', 'Başlangıç', 'Bitiş', 'Program', 'Yayın Türü', 'Aktif'];
        $sheet->fromArray([$headers], null, 'A1');

        // Styling header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate dark
            ],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Sample rows
        $sampleData = [
            ['Pazartesi', '08:30', '10:00', 'Bab-ı Reyyan', 'Normal', 'Evet'],
            ['Pazartesi', '10:00', '11:30', 'Kalbe Şifa Ayetler', 'Tekrar Yayın', 'Evet'],
            ['Salı', '08:30', '10:00', 'Mukabele', 'Normal', 'Evet'],
            ['Çarşamba', '14:00', '15:30', 'Cuma Sohbetleri', 'Canlı Yayın', 'Evet'],
            ['Perşembe', '20:00', '21:30', 'Asr-ı Saadet', 'Normal', 'Evet'],
            ['Cuma', '12:00', '13:30', 'Cuma Vaazı', 'Canlı Yayın', 'Evet'],
            ['Cumartesi', '15:00', '16:30', 'Gençlik Sohbetleri', 'Normal', 'Evet'],
            ['Pazar', '18:00', '19:30', 'Haftanın Özeti', 'Tekrar Yayın', 'Evet'],
        ];

        $sheet->fromArray($sampleData, null, 'A2');

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempDir = sys_get_temp_dir();
        $filePath = $tempDir . '/Yayın_Akışı_Excel_Şablonu_' . uniqid() . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * Parses and validates uploaded Excel / CSV file.
     *
     * @param string|UploadedFile $file Input file path or UploadedFile
     * @return array Result summary and rows preview
     */
    public function parseAndValidate(string|UploadedFile $file): array
    {
        $realPath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($realPath) || filesize($realPath) === 0) {
            return [
                'has_errors' => true,
                'total_count' => 0,
                'valid_count' => 0,
                'error_count' => 1,
                'general_error' => 'Dosya bulunamadı veya boş.',
                'rows' => [],
                'errors' => [
                    [
                        'row_num' => '-',
                        'program_name' => '-',
                        'message' => 'Dosya bulunamadı veya boş.',
                    ],
                ],
            ];
        }

        try {
            $spreadsheet = IOFactory::load($realPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            Log::error('Excel load error: ' . $e->getMessage());
            return [
                'has_errors' => true,
                'total_count' => 0,
                'valid_count' => 0,
                'error_count' => 1,
                'general_error' => 'Excel dosyası okunamadı: ' . $e->getMessage(),
                'rows' => [],
                'errors' => [
                    [
                        'row_num' => '-',
                        'program_name' => '-',
                        'message' => 'Excel dosyası okunamadı: ' . $e->getMessage(),
                    ],
                ],
            ];
        }

        if (empty($data) || count($data) < 2) {
            return [
                'has_errors' => true,
                'total_count' => 0,
                'valid_count' => 0,
                'error_count' => 1,
                'general_error' => 'Excel dosyası boş veya başlık satırı dışında veri içermiyor.',
                'rows' => [],
                'errors' => [
                    [
                        'row_num' => '-',
                        'program_name' => '-',
                        'message' => 'Excel dosyası boş veya başlık satırı dışında veri içermiyor.',
                    ],
                ],
            ];
        }

        // 1. Map Header Columns
        $headerRow = array_shift($data);
        $colMap = $this->mapHeaders($headerRow);

        $missingHeaders = [];
        foreach (['day', 'start_time', 'end_time', 'program'] as $requiredKey) {
            if (! isset($colMap[$requiredKey])) {
                $missingHeaders[] = match ($requiredKey) {
                    'day' => 'Gün',
                    'start_time' => 'Başlangıç',
                    'end_time' => 'Bitiş',
                    'program' => 'Program',
                };
            }
        }

        if (! empty($missingHeaders)) {
            return [
                'has_errors' => true,
                'total_count' => 0,
                'valid_count' => 0,
                'error_count' => 1,
                'general_error' => 'Eksik zorunlu sütun(lar): ' . implode(', ', $missingHeaders),
                'rows' => [],
                'errors' => [
                    [
                        'row_num' => 1,
                        'program_name' => '-',
                        'message' => 'Zorunlu sütunlar eksik: ' . implode(', ', $missingHeaders),
                    ],
                ],
            ];
        }

        // 2. Load all programs into memory (0 N+1 queries)
        $programs = Program::all();
        $programLookup = [];
        foreach ($programs as $prog) {
            $rawName = trim($prog->name);
            $programLookup[$rawName] = $prog;
            $programLookup[mb_strtolower($rawName, 'UTF-8')] = $prog;
            $programLookup[$this->normalizeString($rawName)] = $prog;
        }

        $processedRows = [];
        $errors = [];
        $seenKeys = [];
        $daySchedules = []; // for overlap check per day

        $rowNum = 1; // 1-indexed header, data starts at 2
        foreach ($data as $rawRow) {
            $rowNum++;

            // Skip completely empty rows
            if ($this->isEmptyRow($rawRow)) {
                continue;
            }

            $rawDay = trim((string) ($rawRow[$colMap['day']] ?? ''));
            $rawStart = trim((string) ($rawRow[$colMap['start_time']] ?? ''));
            $rawEnd = trim((string) ($rawRow[$colMap['end_time']] ?? ''));
            $rawProgram = trim((string) ($rawRow[$colMap['program']] ?? ''));
            $rawType = trim((string) ($rawRow[$colMap['type'] ?? ''] ?? 'Normal'));
            $rawActive = trim((string) ($rawRow[$colMap['is_active'] ?? ''] ?? 'Evet'));

            $rowErrors = [];

            // Validate Day
            $dayOfWeek = $this->parseDayOfWeek($rawDay);
            if ($dayOfWeek === null) {
                $rowErrors[] = "Geçersiz gün: '{$rawDay}' (Kabul edilen: Pazartesi-Pazar)";
            }

            // Validate Times
            $startTimeFormatted = $this->formatTime($rawStart);
            $endTimeFormatted = $this->formatTime($rawEnd);

            if (! $startTimeFormatted) {
                $rowErrors[] = "Geçersiz başlangıç saati: '{$rawStart}'";
            }
            if (! $endTimeFormatted) {
                $rowErrors[] = "Geçersiz bitiş saati: '{$rawEnd}'";
            }

            if ($startTimeFormatted && $endTimeFormatted) {
                if ($startTimeFormatted >= $endTimeFormatted) {
                    // Check overnight
                    if ($startTimeFormatted > '20:00' && $endTimeFormatted < '06:00') {
                        $rowErrors[] = 'Gece yarısını geçen yayınlar mevcut sistemde desteklenmiyor.';
                    } else {
                        $rowErrors[] = "Bitiş saati ({$endTimeFormatted}) başlangıç saatinden ({$startTimeFormatted}) sonra olmalıdır.";
                    }
                }
            }

            // Validate Program
            $matchedProgram = null;
            if (empty($rawProgram)) {
                $rowErrors[] = 'Program adı zorunludur.';
            } else {
                $matchedProgram = $this->findProgram($rawProgram, $programLookup);
                if (! $matchedProgram) {
                    $rowErrors[] = "Bu program sistemde bulunamadı: '{$rawProgram}'";
                }
            }

            // Validate Broadcast Type
            $typeNormalized = mb_strtolower($rawType, 'UTF-8');
            $broadcastConfig = self::BROADCAST_TYPES[$typeNormalized] ?? null;
            if (! $broadcastConfig && ! empty($rawType)) {
                $rowErrors[] = "Geçersiz yayın türü: '{$rawType}' (Kabul edilen: Normal, Tekrar Yayın, Canlı Yayın, Özel Yayın)";
                $broadcastConfig = ['is_live' => false, 'is_repeat' => false, 'note' => null];
            } elseif (! $broadcastConfig) {
                $broadcastConfig = ['is_live' => false, 'is_repeat' => false, 'note' => null];
            }

            // Validate Active Flag
            $isActive = $this->parseBoolean($rawActive);

            // Duplicate Check in File
            $duplicateKey = "{$dayOfWeek}_{$startTimeFormatted}_{$endTimeFormatted}_" . ($matchedProgram?->id ?? $rawProgram);
            if (isset($seenKeys[$duplicateKey])) {
                $rowErrors[] = 'Bu satır daha önce eklenmiş (Aynı gün, saat ve program duplicate).';
            } else {
                $seenKeys[$duplicateKey] = true;
            }

            // Time Overlap Check within the same day
            if ($dayOfWeek !== null && $startTimeFormatted && $endTimeFormatted) {
                if (! isset($daySchedules[$dayOfWeek])) {
                    $daySchedules[$dayOfWeek] = [];
                }

                foreach ($daySchedules[$dayOfWeek] as $existingItem) {
                    $eStart = $existingItem['start'];
                    $eEnd = $existingItem['end'];

                    // Overlap logic: (StartA < EndB) and (EndA > StartB)
                    if ($startTimeFormatted < $eEnd && $endTimeFormatted > $eStart) {
                        $rowErrors[] = "Yayın çakışması! {$rawDay} {$startTimeFormatted}-{$endTimeFormatted} aralığı, başka bir satırdaki {$eStart}-{$eEnd} yayınıyla çakışıyor.";
                        break;
                    }
                }

                if (empty($rowErrors)) {
                    $daySchedules[$dayOfWeek][] = [
                        'start' => $startTimeFormatted,
                        'end' => $endTimeFormatted,
                        'row' => $rowNum,
                    ];
                }
            }

            $status = empty($rowErrors) ? 'ready' : 'error';

            $rowResult = [
                'row_num' => $rowNum,
                'raw_day' => $rawDay,
                'day_of_week' => $dayOfWeek,
                'day_name' => $dayOfWeek !== null ? (Schedule::DAYS[$dayOfWeek] ?? $rawDay) : $rawDay,
                'raw_start' => $rawStart,
                'start_time' => $startTimeFormatted,
                'raw_end' => $rawEnd,
                'end_time' => $endTimeFormatted,
                'raw_program' => $rawProgram,
                'program_id' => $matchedProgram?->id,
                'program_name' => $matchedProgram?->name ?? $rawProgram,
                'raw_type' => $rawType,
                'is_live' => $broadcastConfig['is_live'],
                'is_repeat' => $broadcastConfig['is_repeat'],
                'note' => $broadcastConfig['note'],
                'raw_active' => $rawActive,
                'is_active' => $isActive,
                'status' => $status,
                'errors' => $rowErrors,
            ];

            $processedRows[] = $rowResult;

            if (! empty($rowErrors)) {
                foreach ($rowErrors as $err) {
                    $errors[] = [
                        'row_num' => $rowNum,
                        'program_name' => $rawProgram ?: '-',
                        'message' => $err,
                    ];
                }
            }
        }

        $hasErrors = count($errors) > 0;
        $totalCount = count($processedRows);
        $validCount = count(array_filter($processedRows, fn ($r) => $r['status'] === 'ready'));
        $errorCount = count(array_filter($processedRows, fn ($r) => $r['status'] === 'error'));

        return [
            'has_errors' => $hasErrors,
            'total_count' => $totalCount,
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'rows' => $processedRows,
            'errors' => $errors,
        ];
    }

    /**
     * Executes DB transaction to save validated rows into ScheduleTemplate.
     */
    public function importToTemplate(ScheduleTemplate $template, array $validRows): int
    {
        return DB::transaction(function () use ($template, $validRows) {
            $createdCount = 0;

            foreach ($validRows as $row) {
                if (($row['status'] ?? '') !== 'ready') {
                    continue;
                }

                ScheduleTemplateItem::create([
                    'schedule_template_id' => $template->id,
                    'program_id' => $row['program_id'],
                    'day_of_week' => $row['day_of_week'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'custom_title' => null,
                    'is_live' => $row['is_live'],
                    'is_repeat' => $row['is_repeat'],
                    'is_active' => $row['is_active'],
                    'note' => $row['note'],
                ]);

                $createdCount++;
            }

            return $createdCount;
        });
    }

    /**
     * Generates error Excel report file for downloadable errors.
     */
    public function generateErrorExport(array $errors): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('İçe Aktarma Hataları');

        $headers = ['Excel Satır No', 'Program Adı', 'Hata Açıklaması'];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC2626'], // Red
            ],
        ];
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        $exportData = [];
        foreach ($errors as $err) {
            $exportData[] = [
                $err['row_num'] ?? '-',
                $err['program_name'] ?? '-',
                $err['message'] ?? '-',
            ];
        }

        $sheet->fromArray($exportData, null, 'A2');

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempDir = sys_get_temp_dir();
        $filePath = $tempDir . '/Yayın_Akışı_İçe_Aktarma_Hataları_' . uniqid() . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    protected function mapHeaders(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $colLetter => $headerText) {
            if ($headerText === null) {
                continue;
            }

            $normalized = $this->normalizeString((string) $headerText);

            if (str_contains($normalized, 'gun') || str_contains($normalized, 'day')) {
                $map['day'] = $colLetter;
            } elseif (str_contains($normalized, 'baslangic') || str_contains($normalized, 'start')) {
                $map['start_time'] = $colLetter;
            } elseif (str_contains($normalized, 'bitis') || str_contains($normalized, 'end')) {
                $map['end_time'] = $colLetter;
            } elseif (str_contains($normalized, 'program')) {
                $map['program'] = $colLetter;
            } elseif (str_contains($normalized, 'tur') || str_contains($normalized, 'type')) {
                $map['type'] = $colLetter;
            } elseif (str_contains($normalized, 'aktif') || str_contains($normalized, 'active')) {
                $map['is_active'] = $colLetter;
            }
        }

        return $map;
    }

    protected function parseDayOfWeek(string $rawDay): ?int
    {
        $norm = $this->normalizeString($rawDay);

        return self::DAY_MAP[$norm] ?? null;
    }

    protected function findProgram(string $rawProgram, array $programLookup): ?Program
    {
        $trimmed = trim($rawProgram);

        // 1. Exact match
        if (isset($programLookup[$trimmed])) {
            return $programLookup[$trimmed];
        }

        // 2. Case-insensitive lower
        $lower = mb_strtolower($trimmed, 'UTF-8');
        if (isset($programLookup[$lower])) {
            return $programLookup[$lower];
        }

        // 3. Normalized string match
        $norm = $this->normalizeString($trimmed);
        if (isset($programLookup[$norm])) {
            return $programLookup[$norm];
        }

        return null;
    }

    protected function formatTime(string $rawTime): ?string
    {
        if (empty($rawTime)) {
            return null;
        }

        // Handle Excel numeric time float (e.g., 0.35416666666667 for 08:30)
        if (is_numeric($rawTime) && (float) $rawTime < 1.0) {
            $totalSeconds = (int) round((float) $rawTime * 86400);
            $hours = (int) floor($totalSeconds / 3600);
            $minutes = (int) floor(($totalSeconds % 3600) / 60);

            return sprintf('%02d:%02d', $hours, $minutes);
        }

        // Standard string HH:MM or HH:MM:SS
        if (preg_match('/^(\d{1,2})[:\.](\d{2})(?:[:\.]\d{2})?$/', $rawTime, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];

            if ($h >= 0 && $h < 24 && $min >= 0 && $min < 60) {
                return sprintf('%02d:%02d', $h, $min);
            }
        }

        return null;
    }

    protected function parseBoolean(string $rawActive): bool
    {
        $norm = $this->normalizeString($rawActive);

        return in_array($norm, ['evet', '1', 'true', 'yes', 'aktif'], true);
    }

    protected function normalizeString(string $str): string
    {
        $str = mb_strtolower(trim($str), 'UTF-8');
        $trMap = [
            'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'i' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
        ];

        return strtr($str, $trMap);
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $val) {
            if ($val !== null && trim((string) $val) !== '') {
                return false;
            }
        }

        return true;
    }
}
