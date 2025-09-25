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
$answers = isset($input['answers']) && is_array($input['answers']) ? $input['answers'] : [];
$code = isset($input['code']) ? trim((string)$input['code']) : '';

if ($code === '' && isset($_SESSION['verified_code'])) {
    $code = $_SESSION['verified_code'];
}

if ($code === '') {
    echo json_encode(['ok' => false, 'error' => 'Sifra je obavezna.']);
    exit;
}

$dataDir = __DIR__ . '/../data';
$formPath = $dataDir . '/porudzbine.xlsx';

try {
    createIfMissing($formPath, ['Timestamp', 'Sifra', 'Ime', 'OstalaPoljaJSON'], 'Porudzbine');
    excel_save_form_response($formPath, 'Porudzbine', $code, $answers);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Greška prilikom čuvanja.']);
    exit;
}

echo json_encode(['ok' => true]);
