<?php
// save_event.php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$dbname = 'stcalendar';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['rs' => '0', 'msg' => '資料庫連線失敗']);
    exit;
}

// 1. 接收前端 POST 資料
$publisher   = $_POST['event_publisher'] ?? '';
$location    = $_POST['event_location'] ?? '';
$start_date  = $_POST['event_start_date'] ?? '';
$end_date    = $_POST['event_end_date'] ?? '';
$lector      = $_POST['event_lector'] ?? '';
$organizer   = $_POST['event_organizer'] ?? '';
$implementer = $_POST['event_implementer'] ?? '';
$title       = $_POST['event_title'] ?? '';
$note        = $_POST['event_note'] ?? '';

// 2. 自動處理 event_date (從開始時間擷取日期部分)
$event_date = null;
if (!empty($start_date)) {
    $event_date = date('Y-m-d', strtotime($start_date));
}

// 3. 必填檢查 (修正原本會報錯的判斷式)
if (empty($title) || empty($start_date)) {
    echo json_encode(['rs' => '0', 'msg' => '活動名稱與開始時間為必填項']);
    exit;
}

// 4. 執行新增 SQL
$sql = "INSERT INTO calendar_events (
    event_publisher, event_location, event_start_date, event_end_date, 
    event_lector, event_organizer, event_implementer, event_title, 
    event_note, event_category, event_date
) VALUES (
    :publisher, :location, :start_date, :end_date, 
    :lector, :organizer, :implementer, :title, 
    :note, :category, :event_date
)";

try {
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':publisher'   => $publisher,
        ':location'    => $location,
        ':start_date'  => $start_date,
        ':end_date'    => $end_date,
        ':lector'      => $lector,
        ':organizer'   => $organizer,
        ':implementer' => $implementer,
        ':title'       => $title,
        ':note'        => $note,
        ':category'    => '一般', // 預設分類
        ':event_date'  => $event_date
    ]);

    if ($result) {
        echo json_encode(['rs' => '1', 'msg' => '儲存成功']);
    } else {
        echo json_encode(['rs' => '0', 'msg' => '儲存失敗']);
    }
} catch (PDOException $e) {
    echo json_encode(['rs' => '0', 'msg' => 'SQL錯誤：' . $e->getMessage()]);
}
