function escapeHTML(str) {
    if (str === null || str === undefined) {
        return '';
    }
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

var ym = moment().format("YYYY-MM");
var eventsData = {}; // 儲存後端抓回來的原始資料
let currentViewEvent = null; // 全域變數：儲存目前正在檢視的單筆事件

$(function () {
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

    // --- 2. 儲存與更新邏輯 ---
    $(document).on("click", "#saveEventBtn", function () {
        let formData = $("#addEventForm").serialize();
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        formData += `&csrf_token=${csrfToken}`; // Add CSRF token to form data

        $.ajax({
            url: 'api/save_event.php', // Corrected URL
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

    // --- 3. 刪除邏輯 ---
    $(document).on("click", "#btnDeleteInView", function () {
        if (!currentViewEvent) return;

        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (confirm("您確定要刪除「" + escapeHTML(currentViewEvent.event_title) + "」嗎？")) { // Escaped title in confirm
            $.ajax({
                url: 'api/delete_event.php', // Corrected URL
                type: 'POST',
                data: {
                    event_id: currentViewEvent.event_id,
                    csrf_token: csrfToken // Add CSRF token
                },
                dataType: 'json',
                success: function (response) {
                    if (response.rs == "1") {
                        var modalEl = document.getElementById('viewEventModal');
                        var modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                        $('.modal-backdrop').remove();

                        showSwal("刪除成功", true, "", function () {
                            initscal(ym);
                        });
                    } else {
                        showSwal(response.msg || "刪除失敗", false);
                    }
                },
                error: function () {
                    showSwal("請求失敗，請檢查網路或伺服器設定。", false);
                }
            });
        }
    });

    // --- 4. 編輯按鈕邏輯 (從檢視視窗跳到編輯視窗) ---
    $(document).on("click", "#btnEditInView", function () {
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
        $("input[name='event_start_date']").val(d.event_start_date.replace(" ", "T").substring(0, 16));
        $("input[name='event_end_date']").val(d.event_end_date ? d.event_end_date.replace(" ", "T").substring(0, 16) : "");
        $("input[name='event_location']").val(d.event_location);
        $("input[name='event_lector']").val(d.event_lector);
        $("input[name='event_organizer']").val(d.organizer);
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
        url: 'api/get_events.php',
        type: 'GET',
        data: { ym: InYM },
        dataType: 'json',
        success: function (events) {
            eventsData = events; 
            
            // 1. 【修正】在所有迴圈開始前，先取得並清空備註容器
            const $summaryContainer = $('#month-notes-summary');
            $summaryContainer.empty();
            let hasNote = false;
            const todayStr = moment().format("YYYY-MM-DD");

            const calendarBody = $("#tb").find("tbody")[0];

            $.each(events, function (dateKey, eventList) {
                const cell = calendarBody.querySelector(`#m_${dateKey}`);
                if (!cell) return;

                const fragment = document.createDocumentFragment();

                // 遍歷當天的所有活動
                $.each(eventList, function (i, item) {
                    // --- 渲染日曆格子內的活動塊 ---
                    const eventItemBox = document.createElement('div');
                    eventItemBox.className = 'event-item-box';
                    eventItemBox.title = `${escapeHTML(item.event_title)} (${escapeHTML(item.event_implementer)} ${escapeHTML(item.event_location)})`;
                    eventItemBox.onclick = function (event) {
                        event.stopPropagation();
                        viewEventDetail(dateKey, i);
                    };

                    const eventTimeRow = document.createElement('div');
                    eventTimeRow.className = 'event-time-row';
                    eventTimeRow.textContent = `${moment(item.event_start_date).format('HH:mm')}-${moment(item.event_end_date).format('HH:mm')}`;
                    
                    const eventNameRow = document.createElement('div');
                    eventNameRow.className = 'event-name-row';
                    eventNameRow.textContent = `${item.event_title}(${item.event_implementer})`;

                    eventItemBox.append(eventTimeRow, eventNameRow);
                    fragment.appendChild(eventItemBox);

                    // --- 2. 【修正】備註彙整邏輯 ---
                    if (item.event_note && item.event_note.trim() !== "") {
                        hasNote = true;
                        const shortDate = moment(item.event_start_date).format("MM/DD HH:mm");
                        const isToday = (dateKey === todayStr);
                        const todayClass = isToday ? "is-today-note" : "";

                        const noteCard = document.createElement('div');
                        noteCard.className = `note-card-row ${todayClass}`;

                        const sidebar = document.createElement('div');
                        sidebar.className = 'note-card-sidebar';

                        const body = document.createElement('div');
                        body.className = 'note-card-body';

                        const timeTag = document.createElement('span');
                        timeTag.className = 'note-time-tag';
                        timeTag.textContent = shortDate;

                        const divider = document.createElement('span');
                        divider.className = 'note-divider';
                        divider.textContent = '|';

                        const textContent = document.createElement('span');
                        textContent.className = 'note-text-content';

                    /*     const strongTitle = document.createElement('strong');
                        strongTitle.textContent = item.event_title + "：";
                        
                        textContent.appendChild(strongTitle); */
                        textContent.appendChild(document.createTextNode(item.event_note));

                        body.append(timeTag, divider, textContent);
                        noteCard.append(sidebar, body);

                        // 直接附加到容器，不會被覆蓋
                        $summaryContainer.append(noteCard);
                    }
                });

                cell.innerHTML = '';
                cell.appendChild(fragment);
            });

            // 3. 【修正】如果完全沒有備註，顯示提示
            if (!hasNote) {
                $summaryContainer.html('<span class="text-muted small">本月尚無活動備註。</span>');
            }
        },
        error: function () {
            showSwal("讀取資料失敗", false);
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
    currentViewEvent = eventData; 

    const contentDiv = $("#viewEventContent");
    contentDiv.html('<div class="container-fluid px-0"></div>'); // 使用容器包裹
    const container = contentDiv.find('.container-fluid');

    // 輔助函式：建立一列「名稱：值」
    function appendDetailRow(label, value, isHtml = false) {
        const row = `
            <div class="row g-0 border-bottom align-items-center">
                <div class="col-3 bg-light text-secondary text-end py-3 px-3 fw-bold" style="border-right: 1px solid #dee2e6; min-height: 50px;">
                    ${label}：
                </div>
                <div class="col-9 py-3 px-3 text-dark">
                    ${isHtml ? value : escapeHTML(value || '---')}
                </div>
            </div>`;
        container.append(row);
    }

    // --- 依照順序生成欄位 ---
    appendDetailRow('活動名稱', `<b class="text-primary" style="font-size: 1.1rem;">${escapeHTML(eventData.event_title)}</b>`, true);
    
    const timeStr = `${moment(eventData.event_start_date).format('YYYY-MM-DD HH:mm')} 至 ${eventData.event_end_date ? moment(eventData.event_end_date).format('YYYY-MM-DD HH:mm') : '---'}`;
    appendDetailRow('起訖時間', timeStr);

    appendDetailRow('活動地點', eventData.event_location);
    appendDetailRow('講師/主講', eventData.event_lector);
    appendDetailRow('承辦單位', eventData.event_organizer);
    appendDetailRow('承辦人', eventData.event_implementer);
    appendDetailRow('發佈人員', eventData.event_publisher);
    
    // 備註單獨處理樣式
    if (eventData.event_note) {
        appendDetailRow('備註說明', `<div class="p-2 border rounded bg-light" style="white-space: pre-wrap; font-size: 0.95rem;">${escapeHTML(eventData.event_note)}</div>`, true);
    }

    // 顯示 Modal
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
                <i class="fa-solid fa-clipboard-list me-1"></i> 當月備註
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
