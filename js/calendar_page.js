function escapeHTML(str) {
    if (str === null || str === undefined) {
        return '';
    }
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

var ym = moment().format("YYYY-MM");
var eventsData = {}; // 儲存後端抓回來的原始資料
let currentViewEvent = null; // 全域變數：儲存目前正在檢視的單筆事件
var filterLocation = ""; // 全域變數，紀錄目前選的地點

$(function () {
    // 初始化日曆
    initscal(ym);

    // --- 1. 月份切換按鈕 ---
    $(document).on("click", "#calNext", function () {
        ym = moment(ym + "-01").add(1, 'months').format("YYYY-MM");
        initscal(ym);
    });
    $(document).on("click", "#calLast", function () {
        ym = moment(ym + "-01").subtract(1, 'months').format("YYYY-MM");
        initscal(ym);
    });
    $(document).on("click", "#calNow", function () {
        ym = moment().format("YYYY-MM");
        initscal(ym);
    });

    // --- 2. 地點過濾監聽 ---
    $(document).on("change", "#locationFilter", function () {
        filterLocation = $(this).val();
        renderCalendar(); // 直接重新渲染模板，不需要重抓 AJAX
    });

    // --- 3. 儲存與更新邏輯 ---
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
                    var modalEl = document.getElementById('addEventModal');
                    var modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                    $('.modal-backdrop').remove();
                    $('body').css('overflow', 'auto');
                    showSwal("儲存成功", true, "", function () {
                        initscal(ym);
                    });
                } else {
                    showSwal(response.msg || "儲存失敗", false);
                }
            },
            error: function () {
                showSwal("請求失敗，請檢查網路或伺服器設定。", false);
            }
        });
    });

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

    // --- 5. 編輯按鈕邏輯 ---
    $(document).on("click", "#btnEditInView", function () {
        if (!currentViewEvent) return;
        var vModalEl = document.getElementById('viewEventModal');
        bootstrap.Modal.getInstance(vModalEl).hide();
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
        $("input[name='event_organizer']").val(d.event_organizer); // 原本是 .organizer 修正為 .event_organizer
        $("input[name='event_implementer']").val(d.event_implementer);
        $("textarea[name='event_note']").val(d.event_note);
        var addModal = new bootstrap.Modal(document.getElementById('addEventModal'));
        addModal.show();
    });
}); // <--- 這裡是剛才缺少的關鍵閉合

// --- 核心函數：從 API 拿資料 ---
function initscal(InYM) {
    ym = InYM;
    $.ajax({
        url: 'api/get_events.php',
        type: 'GET',
        data: { ym: InYM },
        dataType: 'json',
        success: function (events) {
            eventsData = events;
            renderCalendar();
        },
        error: function () {
            showSwal("讀取資料失敗", false);
        }
    });
}

// --- 核心函數：Handlebars 渲染日曆 ---
function renderCalendar() {
    const startOfMonth = moment(ym + "-01");
    const daysInMonth = startOfMonth.daysInMonth();
    const firstDayOfWeek = startOfMonth.day();
    const todayStr = moment().format("YYYY-MM-DD");

    let rows = [];
    let currentCells = [];
    let allFilteredNotes = [];

    for (let i = 0; i < firstDayOfWeek; i++) {
        currentCells.push({ isMainMonth: false });
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const dateStr = startOfMonth.clone().date(i).format("YYYY-MM-DD");
        const dayEvents = eventsData[dateStr] || [];

        let filteredEvents = dayEvents.map((item, idx) => {
            return { ...item, originalIndex: idx };
        }).filter(item => {
            return filterLocation === "" || (item.event_location && item.event_location.includes(filterLocation));
        }).map(item => {
            return {
                ...item,
                startTime: moment(item.event_start_date).format('HH:mm'),
                endTime: moment(item.event_end_date).format('HH:mm')
            };
        });

        filteredEvents.forEach(item => {
            if (item.event_note && item.event_note.trim() !== "") {
                allFilteredNotes.push({
                    event_note: item.event_note,
                    shortDate: moment(item.event_start_date).format("MM/DD HH:mm"),
                    isToday: dateStr === todayStr
                });
            }
        });

        currentCells.push({
            date: dateStr,
            dayNum: i,
            isMainMonth: true,
            isToday: dateStr === todayStr,
            filteredEvents: filteredEvents
        });

        if (currentCells.length === 7) {
            rows.push({ cells: currentCells });
            currentCells = [];
        }
    }

    if (currentCells.length > 0) {
        while (currentCells.length < 7) currentCells.push({ isMainMonth: false });
        rows.push({ cells: currentCells });
    }

    const source = $("#calendar-template").html();
    const template = Handlebars.compile(source);
    const html = template({ rows: rows, notes: allFilteredNotes });

    $("#tb").html(html);
    $("#displayYM").text(ym);
}

// --- 輔助函數 ---
function viewEventDetail(date, index) {
    if (!eventsData[date] || !eventsData[date][index]) return;
    const eventData = eventsData[date][index];
    currentViewEvent = eventData;

    const context = {
        fields: [
            { label: '活動名稱', value: `<b class="text-primary">${escapeHTML(eventData.event_title)}</b>`, isHtml: true },
            { label: '起訖時間', value: `${moment(eventData.event_start_date).format('YYYY-MM-DD HH:mm')} 至 ${eventData.event_end_date ? moment(eventData.event_end_date).format('YYYY-MM-DD HH:mm') : '---'}`, isHtml: false },
            { label: '活動地點', value: eventData.event_location, isHtml: false },
            { label: '承辦人', value: eventData.event_implementer, isHtml: false }
        ]
    };

    if (eventData.event_note) {
        context.fields.push({ label: '備註說明', value: `<div class="p-2 border rounded bg-light" style="white-space: pre-wrap;">${escapeHTML(eventData.event_note)}</div>`, isHtml: true });
    }

    const source = $("#event-detail-template").html();
    const template = Handlebars.compile(source);
    $("#viewEventContent").html(template(context));
    new bootstrap.Modal(document.getElementById('viewEventModal')).show();
}

function openAddModal(date) {
    $("#addEventModal .modal-title").text("新增活動");
    $("#addEventForm")[0].reset();
    $("#event_id_input").val("");
    $("#selectedDate").val(date);
    $("input[name='event_start_date']").val(date + "T08:00");
    $("input[name='event_end_date']").val(date + "T17:00");
    new bootstrap.Modal(document.getElementById('addEventModal')).show();
}
