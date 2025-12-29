<?php
// get_events.php
header('Content-Type: application/json; charset=utf-8');

// 資料庫連線設定（請修改成您的實際資料）
$host = 'localhost';
$dbname = 'stcalendar';     // 改成您的資料庫名稱
$username = 'root';     // 改成您的資料庫使用者
$password = '';     // 改成您的資料庫密碼

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
    echo json_encode(['error' => '資料庫連線失敗']);
    exit;
}

// 取得前端傳來的年月參數，若無則預設為當前月份
$ym = $_GET['ym'] ?? date('Y-m');

// 安全計算該月起訖日期
$start_date = "$ym-01";
$last_day = date('Y-m-t', strtotime($start_date));  // 自動取得該月最後一天

// 查詢該月份的所有事件
$sql = "SELECT event_date, event_title 
        FROM calendar_events 
        WHERE event_date BETWEEN :start AND :end 
        ORDER BY event_date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start' => $start_date,
        ':end'   => $last_day
    ]);

    // 組織成前端需要的格式： ["YYYY-MM-DD" => [ ["even" => "標題1"], ["even" => "標題2"] ] ]
    $events = [];
    while ($row = $stmt->fetch()) {
        $date = $row['event_date'];
        $title = htmlspecialchars($row['event_title'], ENT_QUOTES, 'UTF-8');

        if (!isset($events[$date])) {
            $events[$date] = [];
        }
        $events[$date][] = ['even' => $title];
    }

    // 輸出 JSON
    echo json_encode($events, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '查詢失敗']);
}
?>
