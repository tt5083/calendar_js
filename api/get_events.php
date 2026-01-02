<?php
// get_events.php
header('Content-Type: application/json; charset=utf-8');

// Include the new database connection function
require_once '../connection/db.php';

// 強制關閉 PHP 錯誤顯示（避免混入 JSON）
// error_reporting(0); // This can be handled in a central place if needed
// ini_set('display_errors', 0);

try {
    // Get the PDO connection object
    $pdo = get_db_connection();
} catch (Exception $e) {
    // The get_db_connection function already handles and exits on error
    // so this is a fallback.
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get database connection.']);
    exit;
}

// 取得年月參數
$ym = $_GET['ym'] ?? date('Y-m');
$start_date = "$ym-01";
$end_date   = date('Y-m-t', strtotime($start_date));

// 使用 event_date 欄位查詢（您新增的）
$sql = "SELECT * FROM calendar_events 
        WHERE event_date BETWEEN :start AND :end 
          AND event_date IS NOT NULL
        ORDER BY event_date ASC";

// --- get_events.php 的最後部分 ---
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start_date, ':end' => $end_date]);
    $rows = $stmt->fetchAll();

    // 【核心修正】將資料整理成以日期為 Key 的格式
    $eventsByDate = [];
    foreach ($rows as $row) {
        $date = $row['event_date'];
        if (!isset($eventsByDate[$date])) {
            $eventsByDate[$date] = [];
        }
        $eventsByDate[$date][] = $row;
    }

    echo json_encode($eventsByDate); // 輸出 {"2025-12-29": [...], "2025-12-30": [...]}
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}