<?php
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Biblioteka PHPSpreadsheet nije instalirana.']);
    exit;
}
require_once $autoload;

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/env.php';
require_once __DIR__ . '/../lib/excel.php';

load_env(__DIR__ . '/../.env');

$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? trim((string)$input['code']) : '';
$message = isset($input['message']) ? trim((string)$input['message']) : '';

if ($code === '') {
    echo json_encode(['ok' => false, 'error' => 'Šifra je obavezna.']);
    exit;
}

if ($message === '') {
    echo json_encode(['ok' => false, 'error' => 'Poruka je obavezna.']);
    exit;
}

$dataDir = __DIR__ . '/../data';
$logPath = $dataDir . '/pitanja.xlsx';

try {
    createIfMissing($logPath, ['Timestamp', 'SifraIliNepoznat', 'Poruka'], 'Pitanja');
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
    appendRow($logPath, [$timestamp, $code, $message]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Greška prilikom upisa.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'Primljeno, proverićemo status.']);
