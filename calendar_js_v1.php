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
                <!-- 活動總覽中的新增事件移除，以下代碼 -->
                <!-- <button type="button" class="btn btn-primary" id="addMoreEventBtn">新增事件</button> -->
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            </div>
        </div>
    </div>
</div>
<!-- 事件列表視窗 Modal END -->
<script>
    /**
     * 產生單個日曆格子的 HTML
     * @param {string} date 日期字串 YYYY-MM-DD
     * @param {number|string} dayNum 顯示的數字
     * @param {boolean} isMainMonth 是否為當月日期
     */
    function renderCell(date, dayNum, isMainMonth) {
        if (!isMainMonth) {
            return `<td class="cell-empty" style="height:110px;"></td>`;
        }

        const todayStr = moment().format("YYYY-MM-DD");
        const isToday = (date === todayStr);

        // 決定數字顯示 (今天有圓圈)
        const dateDisplay = isToday ?
            `<span class="today-circle">${dayNum}</span>` :
            `<span>${dayNum}</span>`;

        const isTodayClass = isToday ? "today-highlight" : "";

        return `
        <td class="calendar_cell ${isTodayClass}" data-date="${date}">
            <div class="fw-bold date-label" style="font-size: 14px;">${dateDisplay}</div>
            <div id="m_${date}" class="mt-1 event-container"></div>
        </td>`;
    }
    var ym = moment().format("YYYY-MM");
    var eventsData = {};

    $(function() {
        initscal(ym);

        // --- 1. 按鈕事件 (使用靜態父元素代理，確保換月後不失效) ---
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

        // --- 2. 核心點擊邏輯 (修正點擊無反應) ---
        $(document).on("click", ".calendar_cell", function(e) {
            var date = $(this).attr("data-date"); // 使用 attr 比較保險
            if (!date) return;

            // 判斷是否點到活動
            if ($(e.target).hasClass("event-title") || $(e.target).parent().hasClass("event-title")) {
                e.stopPropagation();
                var dayEvents = eventsData[date] || [];
                showEventList(date, dayEvents);
            } else {
                openAddModal(date);
            }
        });

        // --- 3. 儲存邏輯 ---
        $(document).on("click", "#saveEventBtn", function() {
            var formData = $("#addEventForm").serialize();
            $.ajax({
                url: 'save_event.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.rs == "1") {
                        var modalEl = document.getElementById('addEventModal');
                        bootstrap.Modal.getInstance(modalEl).hide();
                        showSwal("儲存成功", true, "", function() {
                            initscal(ym);
                        });
                    } else {
                        showSwal(response.msg || "儲存失敗", false);
                    }
                }
            });
        });
        // 點擊刪除按鈕
        $(document).on("click", ".delete-event-btn", function() {
            var eventId = $(this).data("id");

            // 使用瀏覽器確認視窗
            if (confirm("您確定要刪除這筆活動嗎？刪除後無法還原。")) {
                $.ajax({
                    url: 'delete_event.php',
                    type: 'POST',
                    data: {
                        event_id: eventId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.rs == "1") {
                            // 1. 隱藏目前的總覽視窗
                            var modalEl = document.getElementById('eventListModal');
                            bootstrap.Modal.getInstance(modalEl).hide();

                            // 2. 顯示成功通知
                            showSwal("刪除成功", true, "", function() {
                                // 3. 重新整理日曆資料與畫面
                                initscal(ym);
                            });
                        } else {
                            showSwal(response.msg || "刪除失敗", false);
                        }
                    },
                    error: function() {
                        showSwal("連線發生錯誤", false);
                    }
                });
            }
        });
    });

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
                eventsData = events; // 儲存到全域變數
                $("[id^='m_']").empty();

                $.each(events, function(dateKey, eventList) {
                    var cellId = "#m_" + dateKey;
                    var eventHtml = "";
                    $.each(eventList, function(i, item) {
                        // 【關鍵修正】對應 get_events.php 輸出的欄位名稱
                        var title = item.event_title || "未命名事項";
                        eventHtml += `<div class="event-title text-truncate" title="${title}" style="background-color: #bee5eb; color: #0c5460; padding: 2px 4px; margin-bottom: 2px; font-size: 12px; border-radius: 3px; border: 1px solid #abdde5;">${title}</div>`;
                    });
                    $(cellId).html(eventHtml);
                });
            }
        });
    }

    function CreateCal(InYM) {
        // ... 前方的變數定義 ...
        const startOfMonth = moment(InYM + "-01");
        const daysInMonth = startOfMonth.daysInMonth();
        const firstDayOfWeek = startOfMonth.day();

        let htmlstr = `
        <div class='d-flex align-items-center justify-content-center mb-4'>
        <button id='calLast' class='btn btn-outline-info btn-sm me-3'>
            <i class="fa-solid fa-chevron-left"></i> 上個月
        </button>

        <h3 class='mb-0 mx-2' style='font-weight: 600; min-width: 150px; text-align: center;'>${InYM}</h3>

        <div class='ms-3 d-flex align-items-center'>
            <button id='calNext' class='btn btn-outline-info btn-sm me-4'>
                下個月 <i class="fa-solid fa-chevron-right"></i>
            </button>
            <button id='calNow' class='btn btn-secondary btn-sm'>
                <i class="fa-solid fa-calendar-day"></i> 本月
            </button>
        </div>
    </div>

    <table class='table'>
        `;

        // 1. 起始空白格
        for (let i = 0; i < firstDayOfWeek; i++) {
            htmlstr += renderCell('', '', false);
        }

        // 2. 有日期的格子
        for (let i = 1; i <= daysInMonth; i++) {
            const currentDate = startOfMonth.clone().date(i).format("YYYY-MM-DD");
            htmlstr += renderCell(currentDate, i, true);

            // 換行邏輯
            if ((i + firstDayOfWeek) % 7 === 0 && i !== daysInMonth) {
                htmlstr += "</tr><tr>";
            }
        }

        // 3. 結束空白格
        const totalCellsSoFar = firstDayOfWeek + daysInMonth;
        const remainingCells = (7 - (totalCellsSoFar % 7)) % 7;
        for (let j = 0; j < remainingCells; j++) {
            htmlstr += renderCell('', '', false);
        }

        htmlstr += "</tr></tbody></table>";
        return htmlstr;
    }

    function openAddModal(date) {
        $("#addEventForm")[0].reset();
        $("#selectedDate").val(date);
        $("input[name='event_start_date']").val(date + "T09:00");
        var addModal = new bootstrap.Modal(document.getElementById('addEventModal'));
        addModal.show();
    }

    function showEventList(date, eventList) {
        $("#eventListDateTitle").text(moment(date).format("YYYY年MM月DD日") + " 活動總覽");

        var content = "";
        if (eventList && eventList.length > 0) {
            content = "<div class='list-group shadow-sm'>";
            eventList.forEach(function(item) {
                content += `
                <div class='list-group-item d-flex justify-content-between align-items-center' style='border-left: 5px solid #17a2b8;'>
                    <span class='fw-bold'>${item.event_title}</span>
                    <button class='btn btn-outline-danger btn-sm delete-event-btn' 
                            data-id='${item.event_id}' 
                            title='刪除這筆活動'>
                        <i class='fa-regular fa-trash-can'></i> 刪除
                    </button>
                </div>`;
            });
            content += "</div>";
        } else {
            content = "<p class='text-center text-muted p-4'>目前無活動內容</p>";
        }

        $("#eventListContent").html(content);
        var listModal = new bootstrap.Modal(document.getElementById('eventListModal'));
        listModal.show();
    }
</script>

</html>
