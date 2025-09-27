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

$inputRaw = file_get_contents('php://input');
$input = $inputRaw ? json_decode($inputRaw, true) : [];
$action = isset($input['action']) ? $input['action'] : 'init';
$messageText = isset($input['message']) ? trim((string)$input['message']) : '';

$dataDir = __DIR__ . '/../data';
$formPath = $dataDir . '/form.json';
$formQuestions = [];
if (is_file($formPath)) {
    $json = file_get_contents($formPath);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $formQuestions = $decoded;
    }
}

if (!is_array($formQuestions)) {
    $formQuestions = [];
}

if (!isset($_SESSION['chat_messages']) || !is_array($_SESSION['chat_messages'])) {
    $_SESSION['chat_messages'] = [];
}

if (!isset($_SESSION['form_state']) || !is_array($_SESSION['form_state'])) {
    $_SESSION['form_state'] = [
        'current_index' => 0,
        'waiting_for_answer' => false,
        'answers' => [],
        'completed' => false,
        'saved' => false,
    ];
}

$verifiedCode = $_SESSION['verified_code'] ?? null;
$formState = $_SESSION['form_state'];
$messages = $_SESSION['chat_messages'];

$newMessages = [];

function append_message(array &$messages, string $role, string $content): void
{
    $messages[] = ['role' => $role, 'content' => $content];
    if (count($messages) > 40) {
        $messages = array_slice($messages, -40);
    }
}

function ensure_question(array $formQuestions, array &$formState, array &$messages, array &$newMessages): void
{
    if ($formState['completed'] ?? false) {
        return;
    }
    $current = $formState['current_index'] ?? 0;
    $waiting = $formState['waiting_for_answer'] ?? false;
    if (!$waiting && isset($formQuestions[$current])) {
        $question = (string)($formQuestions[$current]['label'] ?? 'Molimo unesite podatak.');
        append_message($messages, 'assistant', $question);
        $newMessages[] = ['role' => 'assistant', 'content' => $question];
        $formState['waiting_for_answer'] = true;
    }
}

function save_form_if_needed(string $dataDir, array &$formState, ?string $code): void
{
    if (($formState['completed'] ?? false) && !($formState['saved'] ?? false) && $code) {
        $answers = $formState['answers'] ?? [];
        $formPath = $dataDir . '/porudzbine.xlsx';
        createIfMissing($formPath, ['Timestamp', 'Sifra', 'Ime', 'OstalaPoljaJSON'], 'Porudzbine');
        $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
        $ime = $answers['ime'] ?? '';
        $others = $answers;
        unset($others['ime']);
        $json = json_encode($others, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        appendRow($formPath, [$timestamp, $code, $ime, $json]);
        $formState['saved'] = true;
    }
}

if ($action === 'init') {
    if ($verifiedCode && !($formState['completed'] ?? false)) {
        ensure_question($formQuestions, $formState, $messages, $newMessages);
    }

    $_SESSION['chat_messages'] = $messages;
    $_SESSION['form_state'] = $formState;

    echo json_encode([
        'ok' => true,
        'unlocked' => (bool)$verifiedCode,
        'messages' => $messages,
        'formCompleted' => (bool)($formState['completed'] ?? false),
    ]);
    exit;
}

if ($messageText === '') {
    echo json_encode(['ok' => false, 'error' => 'Poruka je obavezna.']);
    exit;
}

append_message($messages, 'user', $messageText);

try {
    $codeForLog = $verifiedCode ?: 'Nepoznat';
    $logPath = $dataDir . '/pitanja.xlsx';
    createIfMissing($logPath, ['Timestamp', 'SifraIliNepoznat', 'Poruka'], 'Pitanja');
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
    appendRow($logPath, [$timestamp, $codeForLog, $messageText]);
} catch (Throwable $e) {
    // ignore logging failure
}

if (!$verifiedCode) {
    $lockedResponse = 'Unesite šifru da biste nastavili sa čatom.';
    append_message($messages, 'assistant', $lockedResponse);
    $newMessages[] = ['role' => 'assistant', 'content' => $lockedResponse];

    $_SESSION['chat_messages'] = $messages;
    $_SESSION['form_state'] = $formState;

    echo json_encode([
        'ok' => true,
        'messages' => $newMessages,
        'formCompleted' => false,
        'locked' => true,
    ]);
    exit;
}

if (!($formState['completed'] ?? false)) {
    $currentIndex = $formState['current_index'] ?? 0;
    $waiting = $formState['waiting_for_answer'] ?? false;
    if ($waiting && isset($formQuestions[$currentIndex])) {
        $question = $formQuestions[$currentIndex];
        $fieldId = (string)($question['id'] ?? ('field_' . $currentIndex));
        $formState['answers'][$fieldId] = $messageText;
        $formState['current_index'] = $currentIndex + 1;
        $formState['waiting_for_answer'] = false;
    }

    if (($formState['current_index'] ?? 0) >= count($formQuestions)) {
        $formState['completed'] = true;
        try {
            save_form_if_needed($dataDir, $formState, $verifiedCode);
        } catch (Throwable $e) {
            $newMessages[] = ['role' => 'assistant', 'content' => 'Greška prilikom čuvanja odgovora.'];
            append_message($messages, 'assistant', 'Greška prilikom čuvanja odgovora.');
            $_SESSION['chat_messages'] = $messages;
            $_SESSION['form_state'] = $formState;
            echo json_encode(['ok' => false, 'error' => 'Greška prilikom čuvanja odgovora.']);
            exit;
        }

        $name = $formState['answers']['ime'] ?? 'prijatelju';
        $finalMessage = "Dragi naš {$name}, Vaš zahtev je poslat našem timu. Uskoro ćete dobiti snimak na Vaš WhatsApp broj.";
        append_message($messages, 'assistant', $finalMessage);
        $newMessages[] = ['role' => 'assistant', 'content' => $finalMessage];
    } else {
        ensure_question($formQuestions, $formState, $messages, $newMessages);
    }

    $_SESSION['chat_messages'] = $messages;
    $_SESSION['form_state'] = $formState;

    echo json_encode([
        'ok' => true,
        'messages' => $newMessages,
        'formCompleted' => (bool)($formState['completed'] ?? false),
    ]);
    exit;
}

$apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
if (!$apiKey) {
    echo json_encode(['ok' => false, 'error' => 'OPENAI_API_KEY nije podešen na serveru.']);
    exit;
}

$history = [];
$start = max(0, count($messages) - 20);
for ($i = $start; $i < count($messages); $i++) {
    $entry = $messages[$i];
    if (!isset($entry['role'], $entry['content'])) {
        continue;
    }
    if ($entry['role'] === 'user' || $entry['role'] === 'assistant') {
        $history[] = $entry;
    }
}

$payload = [
    'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-5-mini',
    'messages' => array_merge([
        [
            'role' => 'system',
            'content' => 'Ti si ljubazan asistent za porudžbine rođendanskih čestitki. Ako sesija nema validnu šifru, odgovaraj samo: “Unesite šifru…”. Kada je forma aktivna, pitaj sledeće pitanje iz form.json. Kad je forma završena, pošalji finalnu poruku i dalje odgovaraj na pitanja korisnika.'
        ]
    ], $history),
    'max_tokens' => 512,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

$response = curl_exec($ch);
if ($response === false) {
    curl_close($ch);
    echo json_encode(['ok' => false, 'error' => 'Neuspešna konekcija ka OpenAI servisu.']);
    exit;
}

$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($statusCode >= 400 || !is_array($data)) {
    $errorMessage = $data['error']['message'] ?? 'Nepoznata greška.';
    echo json_encode(['ok' => false, 'error' => $errorMessage]);
    exit;
}

$assistantMessage = $data['choices'][0]['message']['content'] ?? '';
if ($assistantMessage === '') {
    echo json_encode(['ok' => false, 'error' => 'Prazan odgovor modela.']);
    exit;
}

append_message($messages, 'assistant', $assistantMessage);
$newMessages[] = ['role' => 'assistant', 'content' => $assistantMessage];

$_SESSION['chat_messages'] = $messages;
$_SESSION['form_state'] = $formState;

echo json_encode([
    'ok' => true,
    'messages' => $newMessages,
    'formCompleted' => true,
]);
