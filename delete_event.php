<?php
// delete_event.php
header('Content-Type: application/json; charset=utf-8');

// 資料庫設定
$host = 'localhost';
$dbname = 'stcalendar';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
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
