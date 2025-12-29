<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
    <!-- The fav icon -->
    <link rel="shortcut icon" href="img/favicon.png">
</head>

<body>
    <div id="tb">
    </div>
<!-- 新增事件 Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">新增事件</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addEventForm">
                    <input type="hidden" id="selectedDate" name="selectedDate">

                    <div class="mb-3">
                        <label class="form-label">活動名稱 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="event_title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">開始日期時間 <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" name="event_start_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">結束日期時間</label>
                        <input type="datetime-local" class="form-control" name="event_end_date">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">地點</label>
                        <input type="text" class="form-control" name="event_location">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">備註</label>
                        <textarea class="form-control" name="event_note" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="saveEventBtn">儲存事件</button>
            </div>
        </div>
    </div>
</div>
</body>
<!-- 新增事件 Modal END -->
<!-- 事件列表視窗 Modal -->
<div class="modal fade" id="eventListModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="eventListDateTitle">2025-12-29 的事件</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="eventListContent">
                    <!-- 動態填入事件列表 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="addMoreEventBtn">新增事件</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            </div>
        </div>
    </div>
</div>
 <!-- 事件列表視窗 Modal END -->
<script>
    // 全域變數：目前顯示的年月
    var ym = "2025-12";

    // 頁面載入完成後，先初始化日曆
    $(document).ready(function() {
        initscal(ym);

        // ===== 事件綁定：只寫一次，放在全域，使用事件委派 =====
        // 下一個月
        $(document).on("click", "#calNext", function() {
            var nowym = moment(ym + "-01").add(1, 'months').format("YYYY-MM");
            ym = nowym;
            console.log("Next: " + ym);
            initscal(ym);
        });

        // 上一個月
        $(document).on("click", "#calLast", function() {
            var nowym = moment(ym + "-01").subtract(1, 'months').format("YYYY-MM");
            ym = nowym;
            console.log("Last: " + ym);
            initscal(ym);
        });

        // 本月（今天所屬月份）
        $(document).on("click", "#calNow", function() {
            var nowym = moment().format("YYYY-MM");
            ym = nowym;
            console.log("Now: " + ym);
            initscal(ym);
        });
    });

    // ===== 日曆主函數 =====
function initscal(ym) {
    var htmls = CreateCal(ym);
    $("#tb").html(htmls);

    // 清空所有格子內容
    $("[id^='m_']").html("");

    $.ajax({
        url: 'get_events.php',
        type: 'GET',
        data: { ym: ym },
        dataType: 'json',
        cache: false,
        success: function(events) {
    // 清空所有格子
    $("[id^='m_']").empty();

    // 填入已有事件
    $.each(events, function(dateKey, eventList) {
        var cellId = "#m_" + dateKey;

        var eventHtml = '<div class="event-list">';
        $.each(eventList, function(i, event) {
            eventHtml += '<div class="event-item mb-1">';
            eventHtml += '<span class="event-title pointer text-primary fw-500">' + event.even + '</span>';
            eventHtml += '</div>';
        });
        eventHtml += '</div>';

        $(cellId).html(eventHtml);
    });

    // ===== 核心點擊邏輯：區分「點空白處」vs「點事件名稱」=====
    $("[id^='m_']").off("click.cell").css("cursor", "pointer");

    // 綁定整個格子的點擊（預設行為：開新增表單）
    $("[id^='m_']").on("click.cell", function(e) {
        // 如果點擊的是事件名稱或其子元素 → 停止冒泡，不觸發新增
        if ($(e.target).hasClass("event-title") || $(e.target).closest(".event-title").length > 0) {
            return; // 讓下面的事件標題專屬處理
        }

        // 其餘情況：點到格子空白處 → 開新增表單
        var dateId = $(this).attr("id"); // m_2025-12-31
        var date = dateId.substring(2);

        $("#selectedDate").val(date);
        var defaultStart = date + "T09:00";
        $("input[name='event_start_date']").val(defaultStart);
        $("#addEventForm")[0].reset();
        $("input[name='event_start_date']").val(defaultStart);
        $("input[name='event_title']").focus();

        var addModal = new bootstrap.Modal(document.getElementById('addEventModal'));
        addModal.show();
    });

    // 專門綁定「事件名稱」的點擊 → 開事件列表
    $(".event-title").off("click.title").on("click.title", function(e) {
        e.stopPropagation(); // 關鍵：阻止觸發格子的點擊（新增表單）

        var date = $(this).closest("[id^='m_']").attr("id").substring(2);
        showEventList(date, events[date] || []);
    });
}
    });
}
/* 顯示「空白處」與「事件內容」 */
function showEventList(date, eventList) {
    $("#eventListDateTitle").text(date + " 的事件");
    let content = eventList.length === 0 
        ? "<p class='text-muted'>這天沒有活動</p>" 
        : "<ul class='list-group'>" + 
          eventList.map(e => `<li class='list-group-item'><strong>${e.even}</strong></li>`).join('') + 
          "</ul>";

    $("#eventListContent").html(content);
    
    // 綁定新增按鈕
    $("#addMoreEventBtn").off("click").on("click", function() {
        bootstrap.Modal.getInstance(document.getElementById('eventListModal')).hide();
        openAddModal(date);
    });

    new bootstrap.Modal(document.getElementById('eventListModal')).show();
}

// 2. 獨立出開啟「新增視窗」的邏輯，減少重複代碼
function openAddModal(date) {
    $("#addEventForm")[0].reset();
    $("#selectedDate").val(date);
    $("input[name='event_start_date']").val(date + "T09:00");
    
    var addModal = new bootstrap.Modal(document.getElementById('addEventModal'));
    addModal.show();
}

// 3. 實作儲存功能 (您缺少的部份)
$(document).on("click", "#saveEventBtn", function() {
    // 取得表單數據
    var formData = $("#addEventForm").serialize();
    
    $.ajax({
        url: 'save_event.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.rs == "1") {
                // 1. 關閉視窗
                bootstrap.Modal.getInstance(document.getElementById('addEventModal')).hide();
                // 2. 使用您的 SweetAlert 工具顯示成功訊息
                showSwal("儲存成功", true, "", function() {
                    // 3. 重新整理日曆
                    initscal(ym); 
                });
            } else {
                showSwal(response.msg, false);
            }
        },
        error: function() {
            showFailSwal();
        }
    });
});
/* 顯示「空白處」與「事件內容」 END */



</script>

</html>
