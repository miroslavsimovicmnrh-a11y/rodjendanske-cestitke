<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../lib/excel.php';
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Biblioteka PHPSpreadsheet nije instalirana.']);
    exit;
}

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
    excel_log_question($logPath, 'Pitanja', $codeOrUnknown, $message);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Cannot write log.']);
    exit;
}

echo json_encode(['ok' => true]);
