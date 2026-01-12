<?php
// save_event.php
header('Content-Type: application/json; charset=utf-8');

require_once '../connection/db.php';

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

// 2. 處理日期
$event_date = null;
if (!empty($start_date)) {
    $event_date = date('Y-m-d', strtotime($start_date));
}

try {
    if (!empty($event_id)) {
// --- 判斷是「拖拽更新」還是「完整表單編輯」 ---
        // 檢查是否漏掉 title，如果沒有 title 卻有 start_date，代表這是拖拽行為
        if (!isset($_POST['event_title']) && isset($_POST['event_start_date'])) {
            // 【拖拽模式】：只更新日期相關欄位
            $sql = "UPDATE calendar_events SET 
                    event_start_date = :start_date,
                    event_end_date = :end_date,
                    event_date = :event_date,
                    update_time = NOW()
                    WHERE event_id = :event_id";
            $params = [
                ':start_date'  => $start_date,
                ':end_date'    => $end_date,
                ':event_date'  => $event_date,
                ':event_id'    => $event_id
            ];
        } else {
            // 【表單編輯模式】：更新所有欄位 (你原本的 SQL)
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
        }
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
