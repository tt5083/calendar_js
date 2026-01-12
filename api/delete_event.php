<?php
// delete_event.php
header('Content-Type: application/json; charset=utf-8');

require_once '../connection/db.php';

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
