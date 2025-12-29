<?php
// save_event.php
header('Content-Type: application/json; charset=utf-8');

// 開發階段建議開啟錯誤顯示，正式環境請設為 0
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. 資料庫設定
$host = 'localhost';
$dbname = 'stcalendar';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['rs' => '0', 'msg' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

// 2. 接收並整理 POST 資料
// 注意：這裡的欄位名稱需與您的 HTML form name 屬性對應
$event_title      = $_POST['event_title'] ?? '';
$event_start_date = $_POST['event_start_date'] ?? null;
$event_end_date   = $_POST['event_end_date'] ?? null;
$event_location   = $_POST['event_location'] ?? '';
$event_note       = $_POST['event_note'] ?? '';

// 特別處理：您 SQL 結構中有一個 event_date (date 格式)，
// 通常用於日曆檢索，我們從 event_start_date 擷取日期部分存入
$event_date = null;
if ($event_start_date) {
    $event_date = date('Y-m-d', strtotime($event_start_date));
}

// 3. 基本檢查
if (empty($event_title) || empty($event_start_date)) {
    echo json_encode(['rs' => '0', 'msg' => '活動名稱與開始時間為必填']);
    exit;
}

// 4. 執行新增 (INSERT)
// 根據您的 .sql 結構，部分欄位設為 NOT NULL，需給予預設或空值
$sql = "INSERT INTO calendar_events (
            event_publisher, event_title, event_start_date, 
            event_end_date, event_date, event_location, 
            event_note, event_category, event_organizer, 
            event_implementer, event_lector
        ) VALUES (
            :publisher, :title, :start_date, 
            :end_date, :event_date, :location, 
            :note, :category, :organizer, 
            :implementer, :lector
        )";

try {
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':publisher'   => 'System User', // 暫時寫死或從 Session 取得
        ':title'       => $event_title,
        ':start_date'  => $event_start_date,
        ':end_date'    => $event_end_date,
        ':event_date'  => $event_date,
        ':location'    => $event_location,
        ':note'        => $event_note,
        ':category'    => '一般',       // 預設分類
        ':organizer'   => '',
        ':implementer' => '',
        ':lector'      => ''
    ]);

    if ($result) {
        echo json_encode(['rs' => '1', 'msg' => '儲存成功']);
    } else {
        echo json_encode(['rs' => '0', 'msg' => '儲存失敗']);
    }
} catch (PDOException $e) {
    echo json_encode(['rs' => '0', 'msg' => '資料庫寫入錯誤: ' . $e->getMessage()]);
}
?>
