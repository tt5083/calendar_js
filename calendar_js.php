<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title></title>
    <link href="css/appcsslib.css" rel="stylesheet">
    <link id="bs-css" href="css/bootstrap-cerulean.min.css" rel="stylesheet">
    <link href="css/web-app.css" rel="stylesheet">
    <!-- external javascript -->
    <script src="js/jqlib.js"></script>
    <!-- data table plugin -->
    <script src="js/jqdataTables.js"></script>
    <script src="js/tool.openWindow.js"></script>
    <script src="js/web-app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.7.7/handlebars.min.js"></script>
    <!-- The fav icon -->
    <link rel="shortcut icon" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div id="tb">
    </div>
    <!-- 新增活動 Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-calendar-plus me-2"></i>活動編輯</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEventForm">
                        <input type="hidden" id="event_id_input" name="event_id">

                        <div class="row mt-2">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">活動主題 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" name="event_title" placeholder="請輸入活動名稱" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">發佈人姓名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="event_publisher" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">地點</label>
                                <input type="text" class="form-control" name="event_location" placeholder="例如：會議室A">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label text-primary">開始時間 <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control border-primary" onclick="this.showPicker()" name="event_start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">結束時間</label>
                                <input type="datetime-local" class="form-control" onclick="this.showPicker()" name="event_end_date">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">講師 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="event_lector" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">承辦單位 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="event_organizer" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">承辦人 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="event_implementer" required>
                            </div>

                            <div class="col-12 mb-3">
                                <hr> <label class="form-label">備註事項</label>
                                <textarea class="form-control" name="event_note" rows="2" placeholder="如有其他注意事項請在此填寫"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary px-4" id="saveEventBtn">
                        <i class="fa-solid fa-save me-1"></i> 儲存活動資料
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
<!-- 新增活動 Modal END -->
<!-- 活動列表視窗 Modal -->
<div class="modal fade" id="viewEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa-solid fa-circle-info"></i> 活動詳情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewEventContent">
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-danger" id="btnDeleteInView">
                        <i class="fa-regular fa-trash-can"></i> 刪除
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" id="btnEditInView">
                        <i class="fa-regular fa-pen-to-square"></i> 編輯
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 事件列表視窗 Modal END -->
<script src="js/calendar_page.js"></script>

</html>
