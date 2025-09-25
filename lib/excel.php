<?php
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException('Nedostaje vendor/autoload.php. Pokrenite "composer require phpoffice/phpspreadsheet".');
}
require_once $autoload;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function createIfMissing(string $path, array $headers, string $sheetName = 'Sheet1'): void
{
    if (is_file($path)) {
        return;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetName);
    if (!empty($headers)) {
        $sheet->fromArray($headers, null, 'A1');
    }

    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($path);
}

function excelAppendRow(string $path, string $sheetName, array $headers, array $row): void
{
    createIfMissing($path, $headers, $sheetName);

    $handle = fopen($path, 'c+b');
    if ($handle === false) {
        throw new RuntimeException('Ne može da se otvori fajl za pisanje: ' . $path);
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        throw new RuntimeException('Ne može da se zaključa fajl: ' . $path);
    }

    try {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheetByName($sheetName);
        if ($sheet === null) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);
            if (!empty($headers)) {
                $sheet->fromArray($headers, null, 'A1');
            }
        } elseif ($sheet->getHighestRow() < 1 && !empty($headers)) {
            $sheet->fromArray($headers, null, 'A1');
        }

        $nextRow = $sheet->getHighestRow() + 1;
        if ($sheet->getHighestRow() === 0 && !empty($headers)) {
            $sheet->fromArray($headers, null, 'A1');
            $nextRow = 2;
        }

        $sheet->fromArray($row, null, 'A' . $nextRow);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function excel_log_question(string $path, string $sheetName, string $codeOrUnknown, string $message): void
{
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
    excelAppendRow(
        $path,
        $sheetName,
        ['Timestamp', 'SifraIliNepoznat', 'Poruka'],
        [$timestamp, $codeOrUnknown, $message]
    );
}

function excel_save_form_response(string $path, string $sheetName, string $code, array $answers): void
{
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
    $ime = $answers['ime'] ?? '';
    $others = $answers;
    unset($others['ime']);
    $json = json_encode($others, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    excelAppendRow(
        $path,
        $sheetName,
        ['Timestamp', 'Sifra', 'Ime', 'OstalaPoljaJSON'],
        [$timestamp, $code, $ime, $json]
    );
}
