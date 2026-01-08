<?php
// save_event.php
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
} catch (Exception $e) {
    echo json_encode(['rs' => '0', 'msg' => '資料庫連線失敗']);
    exit;
}

// 1. 接收資料
$event_id    = $_POST['event_id'] ?? ''; // 取得是否有 ID
$publisher   = $_POST['event_publisher'] ?? '';
$location    = $_POST['event_location'] ?? '';
$start_date  = $_POST['event_start_date'] ?? '';
$end_date    = $_POST['event_end_date'] ?? '';
$category = $_POST['event_category'] ?? '';
$lector      = $_POST['event_lector'] ?? '';
$organizer   = $_POST['event_organizer'] ?? '';
$implementer = $_POST['event_implementer'] ?? '';
$title       = $_POST['event_title'] ?? '';
$note        = $_POST['event_note'] ?? '';
// CSRF token is already used, no need to process it further

// 2. 處理日期
$event_date = null;
if (!empty($start_date)) {
    $event_date = date('Y-m-d', strtotime($start_date));
}

try {
    if (!empty($event_id)) {
        // --- 執行更新 (UPDATE) ---
        $sql = "UPDATE calendar_events SET 
                event_publisher = :publisher,
                event_location = :location,
                event_start_date = :start_date,
                event_end_date = :end_date,
                event_category = :category,
                event_lector = :lector,
                event_organizer = :organizer,
                event_implementer = :implementer,
                event_title = :title,
                event_note = :note,
                event_date = :event_date,
                update_time = NOW()
                WHERE event_id = :event_id";

        $params = [
            ':publisher'   => $publisher,
            ':location'    => $location,
            ':start_date'  => $start_date,
            ':end_date'    => $end_date,
            ':lector'      => $lector,
            ':category'    => $category,
            ':organizer'   => $organizer,
            ':implementer' => $implementer,
            ':title'       => $title,
            ':note'        => $note,
            ':event_date'  => $event_date,
            ':event_id'    => $event_id
        ];
    } else {
        // --- 執行新增 (INSERT) ---
        $sql = "INSERT INTO calendar_events (
                    event_publisher, event_location, event_start_date, 
                    event_end_date, event_lector, event_organizer, 
                    event_implementer, event_title, event_note, 
                    event_category, event_date
                ) VALUES (
                    :publisher, :location, :start_date, 
                    :end_date, :lector, :organizer, 
                    :implementer, :title, :note, 
                    :category, :event_date
                )";

        $params = [
            ':publisher'   => $publisher,
            ':location'    => $location,
            ':start_date'  => $start_date,
            ':end_date'    => $end_date,
            ':category'    => $category,
            ':lector'      => $lector,
            ':organizer'   => $organizer,
            ':implementer' => $implementer,
            ':title'       => $title,
            ':note'        => $note,
            ':event_date'  => $event_date
        ];
    }

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode(['rs' => '1', 'msg' => '儲存成功']);
    } else {
        echo json_encode(['rs' => '0', 'msg' => '儲存失敗']);
    }
} catch (PDOException $e) {
    echo json_encode(['rs' => '0', 'msg' => 'SQL錯誤：' . $e->getMessage()]);
}
