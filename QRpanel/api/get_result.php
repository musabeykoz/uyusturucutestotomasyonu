<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$qr_code = isset($_GET['qr']) ? trim($_GET['qr']) : '';

if (empty($qr_code)) {
    echo json_encode([
        'success' => false,
        'error' => 'QR kod parametresi gerekli'
    ]);
    exit;
}

$test_result = getTestResultByQR($qr_code);

if ($test_result) {
    echo json_encode([
        'success' => true,
        'data' => $test_result
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Test sonucu bulunamadı'
    ], JSON_UNESCAPED_UNICODE);
}
?>

