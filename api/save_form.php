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
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
    $ime = $answers['ime'] ?? '';
    $others = $answers;
    unset($others['ime']);
    $json = json_encode($others, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    appendRow($formPath, [$timestamp, $code, $ime, $json]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Greška prilikom čuvanja.']);
    exit;
}

echo json_encode(['ok' => true]);
