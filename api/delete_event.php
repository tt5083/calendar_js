<?php
// delete_event.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../connection/db.php';

// CSRF Token Validation
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['rs' => '0', 'msg' => '無效的請求，CSRF token 驗證失敗。']);
    exit;
}

try {
    $pdo = get_db_connection();
    $event_id = $_POST['event_id'] ?? null;

    if ($event_id) {
        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE event_id = :id");
        $stmt->execute([':id' => $event_id]);
        echo json_encode(['rs' => '1']);
    } else {
        echo json_encode(['rs' => '0', 'msg' => '參數不正確']);
    }
} catch (PDOException $e) {
    echo json_encode(['rs' => '0', 'msg' => '資料庫錯誤']);
}