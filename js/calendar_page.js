/* --- 1. 基礎工具函數 --- */
function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* --- 2. 全域變數 --- */
var ym = moment().format("YYYY-MM");
var eventsData = {};
var currentViewEvent = null;
var filterLocation = "";
var currentView = "month"; // 預設月檢視
var currentWeekStart = moment().startOf('week'); // 週檢視的起點

/* --- 3. 頁面初始化與監聽 --- */
$(function () {
    initscal(ym);

    // 視圖切換：月
    $(document).on("click", "#viewMonth", function () {
        currentView = "month";
        $(".btn-group .btn").removeClass("active");
        $(this).addClass("active");
        renderCalendar();
    });

    // 視圖切換：週
    $(document).on("click", "#viewWeek", function () {
        currentView = "week";
        $(".btn-group .btn").removeClass("active");
        $(this).addClass("active");
        renderCalendar();
    });

    // 導覽控制
    $(document).on("click", "#calNext", function () {
        if (currentView === "month") {
            // 月模式：直接加一個月並重新抓取
            ym = moment(ym + "-01").add(1, 'months').format("YYYY-MM");
            initscal(ym);
        } else {
            // 週模式：
            currentWeekStart.add(1, 'weeks');
            let newYM = currentWeekStart.format("YYYY-MM");

            // 【關鍵檢查】：如果新的一週所在的月份與目前資料月份不同，就重新抓資料
            if (newYM !== ym) {
                ym = newYM;
                initscal(ym);
            } else {
                renderCalendar(); // 同月份，直接重畫即可
            }
        }
    });

    $(document).on("click", "#calLast", function () {
        if (currentView === "month") {
            // 月模式：減一個月
            ym = moment(ym + "-01").subtract(1, 'months').format("YYYY-MM");
            initscal(ym);
        } else {
            // 週模式：
            currentWeekStart.subtract(1, 'weeks');
            let newYM = currentWeekStart.format("YYYY-MM");

            // 【關鍵檢查】：跨月時重新抓取
            if (newYM !== ym) {
                ym = newYM;
                initscal(ym);
            } else {
                renderCalendar();
            }
        }
    });

    $(document).on("click", "#calNow", function () {
        ym = moment().format("YYYY-MM");
        currentWeekStart = moment().startOf('week');
        initscal(ym);
    });

    // 地點過濾
    $(document).on("change", "#locationFilter", function () {
        filterLocation = $(this).val();
        renderCalendar();
    });

    // 儲存與更新
    $(document).on("click", "#saveEventBtn", function () {
        let formData = $("#addEventForm").serialize();
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        formData += `&csrf_token=${csrfToken}`;

        $.ajax({
            url: 'api/save_event.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.rs == "1") {
                    $('#addEventModal').modal('hide');
                    alert("儲存成功");
                    initscal(ym);
                } else {
                    alert(response.msg || "儲存失敗");
                }
            }
        });
    });
});

/* --- 4. 核心渲染流程 --- */

// A. 抓取資料
function initscal(InYM) {
    ym = InYM;
    $.ajax({
        url: 'api/get_events.php',
        type: 'GET',
        data: { ym: InYM },
        dataType: 'json',
        success: function (events) {
            eventsData = events; // 更新全域資料庫
            renderCalendar();    // 執行重繪
        }
    });
}

// B. 渲染派發中心 (您原本缺少的)
function renderCalendar() {
    if (currentView === "month") {
        renderMonthView();
    } else {
        renderWeekView();
    }
}

// C. 月檢視渲染
function renderMonthView() {
    const startOfMonth = moment(ym + "-01");
    const daysInMonth = startOfMonth.daysInMonth();
    const firstDayOfWeek = startOfMonth.day();
    const todayStr = moment().format("YYYY-MM-DD");

    let rows = [];
    let currentCells = [];
    let allFilteredNotes = [];

    // 補空的前面格子
    for (let i = 0; i < firstDayOfWeek; i++) {
        currentCells.push({ isMainMonth: false });
    }

    // 填充日期
    for (let i = 1; i <= daysInMonth; i++) {
        const dateStr = startOfMonth.clone().date(i).format("YYYY-MM-DD");
        const dayEvents = eventsData[dateStr] || [];
        let filtered = processEvents(dayEvents, dateStr, todayStr, allFilteredNotes);

        currentCells.push({
            date: dateStr, dayNum: i, isMainMonth: true, isToday: dateStr === todayStr, filteredEvents: filtered
        });

        if (currentCells.length === 7) {
            rows.push({ cells: currentCells });
            currentCells = [];
        }
    }

    // 補後面的空格子
    if (currentCells.length > 0) {
        while (currentCells.length < 7) currentCells.push({ isMainMonth: false });
        rows.push({ cells: currentCells });
    }

    const source = $("#calendar-template").html();
    const template = Handlebars.compile(source);
    $("#tb").html(template({ rows: rows, notes: allFilteredNotes }));
    $("#displayYM").text(ym);
}

// D. 週檢視渲染
function renderWeekView() {
    const todayStr = moment().format("YYYY-MM-DD");
    const weekNames = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'];
    let days = [];
    let allFilteredNotes = [];

    for (let i = 0; i < 7; i++) {
        const currentDay = currentWeekStart.clone().add(i, 'days');
        const dateStr = currentDay.format("YYYY-MM-DD");
        const dayEvents = eventsData[dateStr] || [];
        
        // 使用統一處理過濾邏輯，這會自動把備註填入 allFilteredNotes
        let filtered = processEvents(dayEvents, dateStr, todayStr, allFilteredNotes);

        days.push({
            date: dateStr, dayNum: currentDay.date(), dayName: weekNames[i], isToday: dateStr === todayStr, filteredEvents: filtered
        });
    }

    const source = $("#week-template").html();
    const template = Handlebars.compile(source);
    $("#tb").html(template({ days: days, notes: allFilteredNotes }));
    $("#displayYM").text(currentWeekStart.format("YYYY-MM-DD") + " 週");
}

// E. 統一處理過濾邏輯
function processEvents(dayEvents, dateStr, todayStr, noteArray) {
    return dayEvents.map((item, idx) => ({ ...item, originalIndex: idx }))
        .filter(item => filterLocation === "" || (item.event_location && item.event_location.includes(filterLocation)))
        .map(item => {
            if (item.event_note && item.event_note.trim() !== "") {
                noteArray.push({
                    event_note: item.event_note,
                    shortDate: moment(item.event_start_date).format("MM/DD HH:mm"),
                    isToday: dateStr === todayStr
                });
            }
            return {
                ...item,
                startTime: moment(item.event_start_date).format('HH:mm'),
                endTime: item.event_end_date ? moment(item.event_end_date).format('HH:mm') : ''
            };
        });
}

/* --- 5. Modal 控制 (全域) --- */
function openAddModal(date) {
    $("#addEventModal .modal-title").text("新增活動");
    const form = document.getElementById('addEventForm');
    if (form) form.reset();
    $("#event_id_input").val("");
    $("input[name='event_start_date']").val(date + "T08:00");
    $("input[name='event_end_date']").val(date + "T17:00");
    $('#addEventModal').modal('show');
}

function viewEventDetail(date, index) {
    if (!eventsData[date] || !eventsData[date][index]) return;
    const eventData = eventsData[date][index];
    currentViewEvent = eventData;

    const source = $("#event-detail-template").html();
    const template = Handlebars.compile(source);
    // 使用 moment 格式化時間，排除秒數
    const startTime = eventData.event_start_date ? moment(eventData.event_start_date).format('YYYY-MM-DD HH:mm') : '';
    const endTime = eventData.event_end_date ? moment(eventData.event_end_date).format('YYYY-MM-DD HH:mm') : '';

    // 這裡建立 Handlebars 需要的 context
    const context = {
        fields: [
            { label: '發佈人', value: eventData.event_publisher, isHtml: false },
            { label: '活動類別', value: eventData.event_category, isHtml: false },
            { label: '活動名稱', value: `<b class="text-primary">${escapeHTML(eventData.event_title)}</b>`, isHtml: true }, 
            { label: '活動開始時間', value: startTime, isHtml: false },
            { label: '活動結束時間', value: endTime, isHtml: false },
            { label: '活動地點', value: eventData.event_location, isHtml: false },
            { label: '講師', value: eventData.event_lector, isHtml: false },
            { label: '承辦人', value: eventData.event_implementer, isHtml: false },
            { label: '承辦單位', value: eventData.event_organizer, isHtml: false },
            { label: '備註', value: eventData.event_note, isHtml: false }

        ]
    };

    $("#viewEventContent").html(template(context));
    $('#viewEventModal').modal('show');
}
// 編輯活動
// 修正後的編輯活動監聽器
$(document).on("click", "#btnEditInView", function () {
    if (!currentViewEvent) return;
    
    // 1. 隱藏詳情視窗
    $('#viewEventModal').modal('hide');
    
    // 2. 延遲一下再開，防止遮罩卡死
    setTimeout(function() {
        $("#addEventModal .modal-title").text("編輯事件");
        $("#addEventForm")[0].reset();
        
        const d = currentViewEvent;
        $("#event_id_input").val(d.event_id);
        $("input[name='event_publisher']").val(d.event_publisher);
        $("input[name='event_title']").val(d.event_title);
        $("input[name='event_start_date']").val(d.event_start_date.replace(" ", "T").substring(0, 16));
        $("input[name='event_end_date']").val(d.event_end_date ? d.event_end_date.replace(" ", "T").substring(0, 16) : "");
        $("input[name='event_location']").val(d.event_location);
        $("input[name='event_lector']").val(d.event_lector);
        $("input[name='event_organizer']").val(d.event_organizer);
        $("input[name='event_implementer']").val(d.event_implementer);
        $("textarea[name='event_note']").val(d.event_note);

        // 3. 改用 jQuery 開啟
        $('#addEventModal').modal('show');
    }, 400);
});

// 刪除活動
// --- 4. 刪除邏輯 ---
$(document).on("click", "#btnDeleteInView", function () {
    if (!currentViewEvent) return;
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (confirm("您確定要刪除「" + escapeHTML(currentViewEvent.event_title) + "」嗎？")) {
        $.ajax({
            url: 'api/delete_event.php',
            type: 'POST',
            data: {
                event_id: currentViewEvent.event_id,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function (response) {
                if (response.rs == "1") {
                    var modalEl = document.getElementById('viewEventModal');
                    bootstrap.Modal.getInstance(modalEl).hide();
                    $('.modal-backdrop').remove();
                    showSwal("刪除成功", true, "", function () {
                        initscal(ym);
                    });
                } else {
                    showSwal(response.msg || "刪除失敗", false);
                }
            }
        });
    }
});

/* 因應月或週檢視狀態，動態 DOM 文字替換(上個月/上週/下個月/下週) */
/* --- 新增：更新導覽按鈕文字的函式 --- */
function updateNavigationButtons() {
    const isMonth = (currentView === "month");
    
    // 選取按鈕並替換 HTML 內容 (保留圖示)
    if (isMonth) {
        $("#calLast").html('<i class="fa-solid fa-chevron-left"></i> 上個月');
        $("#calNext").html('下個月 <i class="fa-solid fa-chevron-right"></i>');
        $("#calNow").text('本月');
    } else {
        $("#calLast").html('<i class="fa-solid fa-chevron-left"></i> 上一週');
        $("#calNext").html('下一週 <i class="fa-solid fa-chevron-right"></i>');
        $("#calNow").text('本週');
    }
}

/* --- 修改：渲染派發中心 --- */
function renderCalendar() {
    // 每次渲染時都更新一次按鈕文字
    updateNavigationButtons();

    if (currentView === "month") {
        renderMonthView();
    } else {
        renderWeekView();
    }
}
