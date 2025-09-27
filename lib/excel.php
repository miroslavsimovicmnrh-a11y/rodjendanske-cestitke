<?php
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException('Biblioteka PHPSpreadsheet nije instalirana.');
}
require_once $autoload;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function createIfMissing($path, $headers, $sheetName = 'Sheet1')
{
    if (!file_exists($path)) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);
        if (!empty($headers)) {
            $sheet->fromArray([$headers], null, 'A1');
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        (new Xlsx($spreadsheet))->save($path);
    }
}

function appendRow($path, $row)
{
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Ne može da se otvori fajl: ' . $path);
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        throw new RuntimeException('Ne može da se zaključa fajl: ' . $path);
    }

    try {
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $nextRow = $sheet->getHighestRow() + 1;
        if ($nextRow < 1) {
            $nextRow = 1;
        }
        $sheet->fromArray([$row], null, 'A' . $nextRow);
        (new Xlsx($spreadsheet))->save($path);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
