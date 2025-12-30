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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div id="tb">
    </div>
    <!-- 新增活動 Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">新增活動</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEventForm">
                        <input type="hidden" id="event_id_input" name="event_id">
                        <div class="mb-3">
                            <label class="form-label">發佈人姓名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="event_publisher" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">地點</label>
                            <input type="text" class="form-control" name="event_location">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">開始日期時間 <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" onclick="this.showPicker()" name="event_start_date" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">結束日期時間</label>
                            <input type="datetime-local" class="form-control" onclick="this.showPicker()" name="event_end_date">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">講師 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="event_lector" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">承辦單位 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="event_organizer" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">承辦人 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="event_implementer" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">主題 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="event_title" required>
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
<!-- 新增活動 Modal END -->
<!-- 活動列表視窗 Modal -->
<div class="modal fade" id="viewEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
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
<script>
    var ym = moment().format("YYYY-MM");
    var eventsData = {}; // 儲存後端抓回來的原始資料
    let currentViewEvent = null; // 全域變數：儲存目前正在檢視的單筆事件

    $(function() {
        initscal(ym);

        // --- 1. 月份切換按鈕 ---
        $(document).on("click", "#calNext", function() {
            ym = moment(ym + "-01").add(1, 'months').format("YYYY-MM");
            initscal(ym);
        });
        $(document).on("click", "#calLast", function() {
            ym = moment(ym + "-01").subtract(1, 'months').format("YYYY-MM");
            initscal(ym);
        });
        $(document).on("click", "#calNow", function() {
            ym = moment().format("YYYY-MM");
            initscal(ym);
        });

        // --- 2. 儲存與更新邏輯 ---
        $(document).on("click", "#saveEventBtn", function() {
            var formData = $("#addEventForm").serialize();
            $.ajax({
                url: 'save_event.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.rs == "1") {
                        // 修正：使用正確的方式關閉 Modal
                        var modalEl = document.getElementById('addEventModal');
                        var modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();

                        // 強制移除可能殘留的灰色遮罩
                        $('.modal-backdrop').remove();
                        $('body').css('overflow', 'auto');

                        showSwal("儲存成功", true, "", function() {
                            initscal(ym);
                        });
                    } else {
                        showSwal(response.msg || "儲存失敗", false);
                    }
                }
            });
        });

        // --- 3. 刪除邏輯 ---
        $(document).on("click", "#btnDeleteInView", function() {
            if (!currentViewEvent) return;
            if (confirm("您確定要刪除「" + currentViewEvent.event_title + "」嗎？")) {
                $.ajax({
                    url: 'delete_event.php',
                    type: 'POST',
                    data: {
                        event_id: currentViewEvent.event_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.rs == "1") {
                            var modalEl = document.getElementById('viewEventModal');
                            var modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) modalInstance.hide();
                            $('.modal-backdrop').remove();

                            showSwal("刪除成功", true, "", function() {
                                initscal(ym);
                            });
                        }
                    }
                });
            }
        });

        // --- 4. 編輯按鈕邏輯 (從檢視視窗跳到編輯視窗) ---
        $(document).on("click", "#btnEditInView", function() {
            if (!currentViewEvent) return;

            // 關閉檢視視窗
            var vModalEl = document.getElementById('viewEventModal');
            bootstrap.Modal.getInstance(vModalEl).hide();

            // 設定編輯模式標題
            $("#addEventModal .modal-title").text("編輯事件");
            $("#addEventForm")[0].reset();

            // 【重要】填入 event_id，後端才會判斷為更新
            $("#event_id_input").val(currentViewEvent.event_id);

            // 填入資料
            const d = currentViewEvent;
            $("#event_id_input").val(d.event_id);
            $("input[name='event_publisher']").val(d.event_publisher);
            $("input[name='event_title']").val(d.event_title);
            $("input[name='event_start_date']").val(d.event_start_date.replace(" ", "T"));
            $("input[name='event_end_date']").val(d.event_end_date ? d.event_end_date.replace(" ", "T") : "");
            $("input[name='event_location']").val(d.event_location);
            $("input[name='event_lector']").val(d.event_lector);
            $("input[name='event_organizer']").val(d.event_organizer);
            $("input[name='event_implementer']").val(d.event_implementer);
            $("textarea[name='event_note']").val(d.event_note);

            // 開啟新增/編輯視窗
            var addModal = new bootstrap.Modal(document.getElementById('addEventModal'));
            addModal.show();
        });
    });

    // --- 核心函數：初始化日曆 ---
    function initscal(InYM) {
        $("#tb").html(CreateCal(InYM));
        $.ajax({
            url: 'get_events.php',
            type: 'GET',
            data: {
                ym: InYM
            },
            dataType: 'json',
            success: function(events) {
                eventsData = events; // 存入全域供點擊時查詢
                // 重新渲染格子內的事件內容
                $.each(events, function(dateKey, eventList) {
                    var cellId = "#m_" + dateKey;
                    var eventHtml = "";
                    $.each(eventList, function(i, item) {
                        eventHtml += `
                            <div class='event-title text-truncate' 
                                 onclick='event.stopPropagation(); viewEventDetail("${dateKey}", ${i})'
                                 title='${item.event_title}'>
                                ${item.event_title}
                            </div>`;
                    });
                    $(cellId).html(eventHtml);
                });
            }
        });
    }

    // --- 核心函數：顯示單一事件詳情 ---
    function viewEventDetail(date, index) {
        if (!eventsData[date] || !eventsData[date][index]) {
            alert("找不到該事項資料");
            return;
        }
        const eventData = eventsData[date][index];
        currentViewEvent = eventData; // 存入全域，供編輯/刪除按鈕讀取

        let html = `
            <table class="table table-sm table-bordered">
                <tr><th class="bg-light" width="30%">活動名稱</th><td><b class="text-primary">${eventData.event_title}</b></td></tr>
                <tr><th class="bg-light">發佈人</th><td>${eventData.event_publisher}</td></tr>
                <tr><th class="bg-light">地點</th><td>${eventData.event_location || '未填寫'}</td></tr>
                <tr><th class="bg-light">時間</th><td>${eventData.event_start_date} <br>至 ${eventData.event_end_date || '-'}</td></tr>
                <tr><th class="bg-light">講師</th><td>${eventData.event_lector || '-'}</td></tr>
                <tr><th class="bg-light">承辦單位</th><td>${eventData.event_organizer || '-'}</td></tr>
                <tr><th class="bg-light">承辦人</th><td>${eventData.event_implementer || '-'}</td></tr>
                <tr><th class="bg-light">備註</th><td>${eventData.event_note || '無'}</td></tr>
            </table>`;

        $("#viewEventContent").html(html);
        var myModal = new bootstrap.Modal(document.getElementById('viewEventModal'));
        myModal.show();
    }

    // --- 核心函數：開啟新增視窗 ---
    function openAddModal(date) {
        $("#addEventModal .modal-title").text("新增活動");
        $("#addEventForm")[0].reset(); // 重置所有輸入
        $("#event_id_input").val(""); // 【重要】確保 ID 被清空，後端才會判斷為新增
        $("#selectedDate").val(date);
        $("input[name='event_start_date']").val(date + "T08:00");
        $("input[name='event_end_date']").val(date + "T17:00");
        var addModal = new bootstrap.Modal(document.getElementById('addEventModal'));
        addModal.show();
    }

    // --- 輔助函數：建立日曆結構 (與您原本的 logic 相同) ---
    function CreateCal(InYM) {
        const startOfMonth = moment(InYM + "-01");
        const daysInMonth = startOfMonth.daysInMonth();
        const firstDayOfWeek = startOfMonth.day();
        let htmlstr = `<div class='d-flex align-items-center justify-content-center mb-4'>
            <button id='calLast' class='btn btn-outline-info btn-sm me-3'><i class="fa-solid fa-chevron-left"></i> 上個月</button>
            <h3 class='mb-0 mx-2' style='font-weight: 600; min-width: 150px; text-align: center;'>${InYM}</h3>
            <button id='calNext' class='btn btn-outline-info btn-sm ms-3'>下個月 <i class="fa-solid fa-chevron-right"></i></button>
            <button id='calNow' class='btn btn-secondary btn-sm ms-3'>本月</button>
        </div><table class='table'><thead><tr><th>星期日</th><th>星期一</th><th>星期二</th><th>星期三</th><th>星期四</th><th>星期五</th><th>星期六</th></tr></thead><tbody><tr>`;

        for (let i = 0; i < firstDayOfWeek; i++) htmlstr += renderCell('', '', false);
        for (let i = 1; i <= daysInMonth; i++) {
            const currentDate = startOfMonth.clone().date(i).format("YYYY-MM-DD");
            htmlstr += renderCell(currentDate, i, true);
            if ((i + firstDayOfWeek) % 7 === 0 && i !== daysInMonth) htmlstr += "</tr><tr>";
        }
        htmlstr += "</tr></tbody>";
        // 新增底部備註列 (使用 tfoot)
        htmlstr += `
        <tfoot class="month-notes-footer">
            <tr>
                <td colspan="1" class="notes-label-cell">
                    <i class="fa-solid fa-clipboard-list me-1"></i> 當月備註彙整
                </td>
                <td colspan="6" class="notes-content-cell">
                    <div id="month-notes-summary">
                        <span class="text-muted small">自動彙整本月所有活動備註...</span>
                    </div>
                </td>
            </tr>
        </tfoot>
    `;

        htmlstr += "</table>"; // 結束表格
        return htmlstr;
    }

    function renderCell(date, dayNum, isMainMonth) {
        if (!isMainMonth) return `<td class="cell-empty"></td>`;
        const todayStr = moment().format("YYYY-MM-DD");
        const isToday = (date === todayStr);
        return `
        <td class="calendar_cell ${isToday ? 'today-highlight' : ''}" data-date="${date}" onclick="openAddModal('${date}')">
            <div class="fw-bold date-label">${isToday ? `<span class="today-circle">${dayNum}</span>` : dayNum}</div>
            <div id="m_${date}" class="mt-1 event-container"></div>
        </td>`;
    }
</script>

</html>
