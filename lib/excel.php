<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function createIfMissing($path, $headers, $sheetName = 'Sheet1') {
  if (!file_exists($path)) {
    $ss = new Spreadsheet();
    $ss->getActiveSheet()->setTitle($sheetName);
    $ss->getActiveSheet()->fromArray([$headers], null, 'A1');
    (new Xlsx($ss))->save($path);
  }
}

function appendRow($path, $row) {
  $fp = fopen($path, 'c+'); flock($fp, LOCK_EX); fclose($fp);
  $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
  $ss = $reader->load($path);
  $sheet = $ss->getActiveSheet();
  $next = $sheet->getHighestRow()+1;
  $sheet->fromArray([$row], null, 'A'.$next);
  (new Xlsx($ss))->save($path);
}

function excel_log_question(string $path, string $sheetName, string $codeOrUnknown, string $message): void {
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
    appendRow($path, [$timestamp, $codeOrUnknown, $message]);
}

function excel_save_form_response(string $path, string $sheetName, string $code, array $answers): void {
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
    $ime = $answers['ime'] ?? '';
    $others = $answers;
    unset($others['ime']);
    $json = json_encode($others, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    appendRow($path, [$timestamp, $code, $ime, $json]);
}
