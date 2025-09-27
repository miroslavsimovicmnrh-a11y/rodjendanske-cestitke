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

load_env(__DIR__ . '/../.env');

$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? trim((string)$input['code']) : '';

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    echo json_encode(['ok' => false, 'error' => 'Data directory missing.']);
    exit;
}

$codesFile = $dataDir . '/valid_codes.txt';
if (!is_file($codesFile)) {
    echo json_encode(['ok' => false, 'error' => 'Codes file missing.']);
    exit;
}

$codes = array_map('trim', file($codesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
$valid = $code !== '' && in_array($code, $codes, true);

if ($valid) {
    $_SESSION['verified_code'] = $code;
    $_SESSION['chat_messages'] = [];
    $_SESSION['form_state'] = [
        'current_index' => 0,
        'waiting_for_answer' => false,
        'answers' => [],
        'completed' => false,
        'saved' => false,
    ];
}

echo json_encode(['ok' => $valid]);
