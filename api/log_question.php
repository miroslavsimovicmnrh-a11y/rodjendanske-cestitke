<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Biblioteka PHPSpreadsheet nije instalirana.'], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $autoload;

require_once __DIR__ . '/../lib/env.php';
require_once __DIR__ . '/../lib/excel.php';

load_env(__DIR__ . '/../.env');

session_start();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim((string)$input['message']) : '';
$code = isset($input['code']) ? trim((string)$input['code']) : '';

if ($message === '') {
    echo json_encode(['ok' => false, 'error' => 'Message is required.']);
    exit;
}

$dataDir = __DIR__ . '/../data';
$path = $dataDir . '/pitanja.xlsx';

if ($code === '' && isset($_SESSION['verified_code'])) {
    $code = $_SESSION['verified_code'];
}

$codeOrUnknown = $code !== '' ? $code : 'Nepoznat';

try {
    createIfMissing($path, ['Timestamp', 'SifraIliNepoznat', 'Poruka'], 'Pitanja');
    excel_log_question($path, 'Pitanja', $codeOrUnknown, $message);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Cannot write log.']);
    exit;
}

echo json_encode(['ok' => true]);
