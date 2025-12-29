<?php
// get_events.php
header('Content-Type: application/json; charset=utf-8');

// 強制關閉 PHP 錯誤顯示（避免混入 JSON）
error_reporting(0);
ini_set('display_errors', 0);

// 資料庫設定（確認您的本機環境）
$host = 'localhost';
$dbname = 'stcalendar';         // 您的資料庫名稱
$username = 'root';             // 本機預設
$password = '';                 // 本機預設空

$pdo = null;  // 先初始化

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => '資料庫連線失敗',
        'details' => $e->getMessage()  // 這裡會顯示真實錯誤，如 "Access denied" 或 "Unknown database"
    ]);
    exit;
}

// 如果連線成功，$pdo 才會有值
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'PDO 物件未初始化']);
    exit;
}

// 取得年月參數
$ym = $_GET['ym'] ?? date('Y-m');
$start_date = "$ym-01";
$end_date   = date('Y-m-t', strtotime($start_date));

// 使用 event_date 欄位查詢（您新增的）
$sql = "SELECT event_date, event_title 
        FROM calendar_events 
        WHERE event_date BETWEEN :start AND :end 
          AND event_date IS NOT NULL
        ORDER BY event_date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start' => $start_date,
        ':end'   => $end_date
    ]);

    $events = [];
    while ($row = $stmt->fetch()) {
        $date = $row['event_date'];
        $title = htmlspecialchars($row['event_title'], ENT_QUOTES, 'UTF-8');

        if (!isset($events[$date])) {
            $events[$date] = [];
        }
        $events[$date][] = ['even' => $title];
    }

    echo json_encode($events, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => '查詢失敗',
        'details' => $e->getMessage()
    ]);
    exit;
}
?>
