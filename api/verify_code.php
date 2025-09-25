<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? trim((string)$input['code']) : '';

$dataDir = realpath(__DIR__ . '/../data');
if ($dataDir === false) {
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
