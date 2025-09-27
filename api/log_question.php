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
$message = isset($input['message']) ? trim((string)$input['message']) : '';
$code = isset($input['code']) ? trim((string)$input['code']) : '';

if ($message === '') {
    echo json_encode(['ok' => false, 'error' => 'Message is required.']);
    exit;
}

$dataDir = __DIR__ . '/../data';
$logPath = $dataDir . '/pitanja.xlsx';

if ($code === '' && isset($_SESSION['verified_code'])) {
    $code = $_SESSION['verified_code'];
}

$codeOrUnknown = $code !== '' ? $code : 'Nepoznat';

try {
    createIfMissing($logPath, ['Timestamp', 'SifraIliNepoznat', 'Poruka'], 'Pitanja');
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
    appendRow($logPath, [$timestamp, $codeOrUnknown, $message]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Cannot write log.']);
    exit;
}

echo json_encode(['ok' => true]);
