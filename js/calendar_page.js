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
let ym = moment().format("YYYY-MM");
let eventsData = {};
let currentViewEvent = null;
let filterLocation = "";
let filterOrganizer = "";
let currentView = "month";
let currentWeekStart = moment().startOf('week');
let lastFocusElement = null;

/* --- 3. 頁面初始化與監聽 --- */
document.addEventListener('DOMContentLoaded', function () {
    initscal(ym);
    // 在顯示 SweetAlert2 之前，或者直接在頁面初始化時執行
    document.addEventListener('focusin', (e) => {
        if (e.target.closest('.swal2-container')) {
            e.stopImmediatePropagation();
        }
    }, true);
    // 在顯示 SweetAlert2 之前，或者直接在頁面初始化時執行 END

    // --- 全域修正：防止 ARIA hidden 警告 ---
    // 當任何 Modal 即將隱藏時，如果焦點還在裡面，強制移出焦點
    // 這能同時解決手動關閉、按 Esc、點擊 backdrop 或按 X 按鈕的情境
    document.addEventListener('hide.bs.modal', function (event) {
        const modal = event.target;
        // 檢查 activeElement 是否在該 modal 內
        if (document.activeElement && (modal.contains(document.activeElement) || modal === document.activeElement)) {
            document.activeElement.blur();
        }
    }, true); // 使用捕獲模式確保盡早執行
    // 視圖切換：月
    document.getElementById('viewMonth')?.addEventListener('click', function () {
        currentView = "month";
        updateActiveButton(this);
        renderCalendar();
    });

    // 視圖切換：週
    document.getElementById('viewWeek')?.addEventListener('click', function () {
        currentView = "week";
        updateActiveButton(this);
        renderCalendar();
    });

    // 導覽控制 (上一個)
    document.getElementById('calLast')?.addEventListener('click', function () {
        if (currentView === "month") {
            ym = moment(ym + "-01").subtract(1, 'months').format("YYYY-MM");
            initscal(ym);
        } else {
            currentWeekStart.subtract(1, 'weeks');
            const newYM = currentWeekStart.format("YYYY-MM");
            if (newYM !== ym) {
                ym = newYM;
                initscal(ym);
            } else {
                renderCalendar();
            }
        }
    });

    // 導覽控制 (下一個)
    document.getElementById('calNext')?.addEventListener('click', function () {
        if (currentView === "month") {
            ym = moment(ym + "-01").add(1, 'months').format("YYYY-MM");
            initscal(ym);
        } else {
            currentWeekStart.add(1, 'weeks');
            const newYM = currentWeekStart.format("YYYY-MM");
            if (newYM !== ym) {
                ym = newYM;
                initscal(ym);
            } else {
                renderCalendar();
            }
        }
    });

    // 回到今天
    document.getElementById('calNow')?.addEventListener('click', function () {
        ym = moment().format("YYYY-MM");
        currentWeekStart = moment().startOf('week');
        initscal(ym);
    });

    // 地點篩選
    document.getElementById('locationFilter')?.addEventListener('change', function () {
        filterLocation = this.value;
        renderCalendar();
    });

    // 承辦單位篩選
    document.getElementById('filterOrganizer')?.addEventListener('change', function () {
        filterOrganizer = this.value;
        renderCalendar();
    });

    // 儲存與更新 (使用 Fetch API)
    document.getElementById('saveEventBtn')?.addEventListener('click', async function () {
        const form = document.getElementById('addEventForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('api/save_event.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.rs == "1") {
                hideModal('addEventModal');
                alert("儲存成功");
                initscal(ym);
            } else {
                alert(result.msg || "儲存失敗");
            }
        } catch (error) {
            console.error('Error saving event:', error);
        }
    });

    // 編輯與刪除按鈕 (放在 Modal 內，使用委派或直接監聽)
    document.getElementById('btnEditInView')?.addEventListener('click', function () {
        if (!currentViewEvent) return;
        hideModal('viewEventModal');

        setTimeout(() => {
            const d = currentViewEvent;
            document.querySelector("#addEventModal .modal-title").textContent = "編輯事件";
            document.getElementById('addEventForm').reset();

            document.getElementById('event_id_input').value = d.event_id;
            document.querySelector("input[name='event_publisher']").value = d.event_publisher || '';
            document.querySelector("input[name='event_title']").value = d.event_title || '';
            document.querySelector("input[name='event_start_date']").value = d.event_start_date.replace(" ", "T").substring(0, 16);
            document.querySelector("input[name='event_end_date']").value = d.event_end_date ? d.event_end_date.replace(" ", "T").substring(0, 16) : "";
            document.querySelector("input[name='event_location']").value = d.event_location || '';
            document.querySelector("input[name='event_lector']").value = d.event_lector || '';
            document.querySelector("input[name='event_organizer']").value = d.event_organizer || '';
            document.querySelector("input[name='event_implementer']").value = d.event_implementer || '';
            document.querySelector("textarea[name='event_note']").value = d.event_note || '';

            showModal('addEventModal');
        }, 400);
    });

    document.getElementById('btnDeleteInView')?.addEventListener('click', function () {
        if (!currentViewEvent) return;

        // 1. 生成題目
        const n1 = Math.floor(Math.random() * 10) + 1;
        const n2 = Math.floor(Math.random() * 10) + 1;
        const ans = n1 + n2;

        // 2. 顯示 SweetAlert2 對話框
        Swal.fire({
            title: '確認刪除活動？',
            html: `您確定要刪除「<b>${currentViewEvent.event_title}</b>」嗎？<br><br>請輸入驗證計算結果：<br><b>${n1} + ${n2} = ?</b>`,
            input: 'text',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '確定刪除',
            cancelButtonText: '取消',
            focusConfirm: false,
            // 驗證輸入內容
            preConfirm: (value) => {
                if (!value) {
                    Swal.showValidationMessage('請輸入計算結果');
                    return false;
                }
                if (parseInt(value) !== ans) {
                    Swal.showValidationMessage('計算錯誤，請重新確認');
                    return false;
                }
                return true;
            }
        }).then(async (result) => {
            // 如果使用者點擊「確定刪除」且通過驗證
            if (result.isConfirmed) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const formData = new URLSearchParams();
                formData.append('event_id', currentViewEvent.event_id);
                formData.append('csrf_token', csrfToken);

                try {
                    const response = await fetch('api/delete_event.php', {
                        method: 'POST',
                        body: formData
                    });
                    const res = await response.json();

                    if (res.rs == "1") {
                        // 關閉原本的活動詳情 Modal
                        hideModal('viewEventModal');

                        // 顯示成功的 SweetAlert
                        Swal.fire({
                            title: '已刪除！',
                            text: '活動已成功從日曆中移除。',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // 重新整理日曆
                        if (typeof initscal === 'function') initscal(ym);
                    } else {
                        Swal.fire('錯誤', res.msg || '刪除失敗', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire('錯誤', '連線到伺服器時發生問題', 'error');
                }
            }
        });
    });
});

/* --- 4. 核心渲染流程 --- */

async function initscal(InYM) {
    ym = InYM;
    try {
        const response = await fetch(`api/get_events.php?ym=${InYM}`);
        const result = await response.json();

        // 同步賦值：這會確保無論是在全域還是 window 下都能抓到
        eventsData = result;
        window.eventsData = result;

        /* console.log("測試資料同步成功，日期：", InYM); */
        renderCalendar();
    } catch (error) {
        console.error('Error fetching events:', error);
    }
}

function renderCalendar() {
    updateNavigationButtons();

    if (currentView === "month") {
        // 1. 月檢視標題
        document.getElementById("displayYM").textContent = ym;
        renderMonthView();
    } else {
        // 2. 週檢視標題 (年. 第 N 週)
        const year = currentWeekStart.format("YYYY");
        const weekNum = currentWeekStart.week();
        document.getElementById("displayYM").textContent = year + " 第 " + weekNum + " 週";
        renderWeekView();
    }
    // 關鍵：渲染完 HTML 後，延遲一點點時間執行初始化
    setTimeout(initDragAndDrop, 150);
}

function renderMonthView() {
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
        const dayEvents = (window.eventsData && window.eventsData[dateStr]) ? window.eventsData[dateStr] : [];
        let filtered = processEvents(dayEvents, dateStr, todayStr, allFilteredNotes);

        currentCells.push({
            date: dateStr, dayNum: i, isMainMonth: true, isToday: dateStr === todayStr, filteredEvents: filtered
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

    const source = document.getElementById("calendar-template").innerHTML;
    const template = Handlebars.compile(source);
    document.getElementById("tb").innerHTML = template({ rows: rows, notes: allFilteredNotes });
    /* 不專一顯示在calnow顯示年月,也會影響到renderWeekView ,renderCalendar 統一控管*/
    /* document.getElementById("displayYM").textContent = ym; */
}

function renderWeekView() {
    const todayStr = moment().format("YYYY-MM-DD");
    const weekNames = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];
    let days = [];
    let allFilteredNotes = [];

    for (let i = 0; i < 7; i++) {
        const currentDay = currentWeekStart.clone().add(i, 'days');
        const dateStr = currentDay.format("YYYY-MM-DD");
        const dayEvents = (window.eventsData && window.eventsData[dateStr]) ? window.eventsData[dateStr] : []; let filtered = processEvents(dayEvents, dateStr, todayStr, allFilteredNotes);
        days.push({
            date: dateStr, dayNum: currentDay.date(), dayName: weekNames[i], isToday: dateStr === todayStr, filteredEvents: filtered
        });
    }

    const source = document.getElementById("week-template").innerHTML;
    const template = Handlebars.compile(source);
    document.getElementById("tb").innerHTML = template({ days: days, notes: allFilteredNotes });
    /* 同renderMonthView,區分顯示月與週的字,不被綁死，renderCalendar 統一控管*/
    /* document.getElementById("displayYM").textContent = currentWeekStart.format("YYYY-MM-DD") + " 週"; */
}

function processEvents(dayEvents, dateStr, todayStr, noteArray) {
    return dayEvents.map((item, idx) => ({ ...item, originalIndex: idx }))
        .filter(item => {
            // 🔹 地點條件：沒選或符合
            const locationMatch = filterLocation === "" ||
                (item.event_location && item.event_location.includes(filterLocation));

            // 🔹 承辦單位條件：沒選或符合
            const organizerMatch = filterOrganizer === "" ||
                (item.event_organizer && item.event_organizer.includes(filterOrganizer));

            // ✅ 兩個條件都要符合（AND 交集）
            return locationMatch && organizerMatch;
        })
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

/* --- 5. Modal 控制 (原生 BS5 API) --- */
function openAddModal(date) {
    // 記錄是哪個格子觸發的，這能讓 Chrome 知道關閉後焦點回哪去
    lastFocusElement = document.activeElement;
    document.querySelector("#addEventModal .modal-title").textContent = "新增活動";
    const form = document.getElementById('addEventForm');
    if (form) form.reset();
    document.getElementById("event_id_input").value = "";
    document.querySelector("input[name='event_start_date']").value = date + "T08:00";
    document.querySelector("input[name='event_end_date']").value = date + "T17:00";

    showModal('addEventModal');
}

function viewEventDetail(date, index) {
    // 1. 強制從 window.eventsData 抓取資料，避免抓到 undefined 的區域變數
    const currentData = (window.eventsData && window.eventsData[date]) ? window.eventsData[date][index] : null;

    // 如果找不到資料就跳出
    if (!currentData) {
        console.error("找不到活動資料:", date, index);
        return;
    }

    // 2. 更新當前視窗使用的活動物件
    currentViewEvent = currentData;

    // 3. 準備 Handlebars 模板
    const source = document.getElementById("event-detail-template").innerHTML;
    const template = Handlebars.compile(source);

    // 4. 格式化時間（直接使用 currentData）
    const startTime = currentData.event_start_date ? moment(currentData.event_start_date).format('YYYY-MM-DD HH:mm') : '';
    const endTime = currentData.event_end_date ? moment(currentData.event_end_date).format('YYYY-MM-DD HH:mm') : '';

    // 5. 構建 context（將所有 eventData 替換成 currentData）
    const context = {
        fields: [
            { label: '發佈人', value: currentData.event_publisher, isHtml: false },
            { label: '活動類別', value: currentData.event_category, isHtml: false },
            { label: '活動名稱', value: `<b class="text-primary">${escapeHTML(currentData.event_title)}</b>`, isHtml: true },
            { label: '活動開始時間', value: startTime, isHtml: false },
            { label: '活動結束時間', value: endTime, isHtml: false },
            { label: '活動地點', value: currentData.event_location, isHtml: false },
            { label: '講師', value: currentData.event_lector, isHtml: false },
            { label: '承辦人', value: currentData.event_implementer, isHtml: false },
            { label: '承辦單位', value: currentData.event_organizer, isHtml: false },
            { label: '備註', value: currentData.event_note, isHtml: false }
        ]
    };

    // 6. 渲染並顯示
    document.getElementById("viewEventContent").innerHTML = template(context);
    showModal('viewEventModal');
}

/* --- 6. 輔助 UI 函數 --- */
function updateNavigationButtons() {
    const isMonth = (currentView === "month");
    const suffix = isMonth ? "個月" : "週";

    document.getElementById("calLast").innerHTML = `<i class="fa-solid fa-chevron-left"></i> 上一${suffix}`;
    document.getElementById("calNext").innerHTML = `下一${suffix} <i class="fa-solid fa-chevron-right"></i>`;
    /* document.getElementById("calNow").textContent = `本${suffix}`; 改今天*/
    document.getElementById("calNow").textContent = "今天";
}

function updateActiveButton(activeBtn) {
    document.querySelectorAll(".view-tabs .btn").forEach(btn => btn.classList.remove("active"));
    activeBtn.classList.add("active");
}

// 封裝 BS5 Modal 原生呼叫，防止 ARIA 衝突
function showModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.removeAttribute('aria-hidden'); // 移除寫死的屬性
        const inst = bootstrap.Modal.getOrCreateInstance(el);
        inst.show();
    }
}

function hideModal(id) {
    const el = document.getElementById(id);
    if (el) {
        const inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
        // 官方建議：在隱藏後手動處理焦點與清理
        el.addEventListener('hidden.bs.modal', function () {
            if (lastFocusElement) {
                lastFocusElement.focus();
                lastFocusElement = null;
            }
            // 強制清理可能導致「點第二次沒反應」的殘留狀態
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }, { once: true });
    }
}

function initDragAndDrop() {
    const containers = document.querySelectorAll('.event-container');

    containers.forEach(container => {
        if (container.classList.contains('drag-ready')) return;
        container.classList.add('drag-ready');

        new Sortable(container, {
            group: 'calendar-events',
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: async function (evt) {
                const eventId = evt.item.getAttribute('data-id');
                const newCell = evt.to.closest('.calendar_cell');
                if (!newCell) return;
                // --- 關鍵修正：檢查是否有日期屬性 ---
                if (!newCell || !newCell.dataset.date) {
                    // 如果移動到了沒有日期的地方，直接重刷日曆讓它彈回原位
                    Swal.fire({
                        icon: 'error',
                        title: '無效的操作',
                        text: '請將活動移動至有效的日期格內',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    initscal(ym);
                    return;
                }
                const newDate = newCell.dataset.date;
                const oldDate = evt.from.closest('.calendar_cell').dataset.date;

                // 只有日期變動才觸發
                if (newDate && newDate !== oldDate) {

                    /* 取得event_title */
                    // --- [新增] 先從全域資料中找出該活動的標題 ---
                    let originalEvent = null;
                    Object.values(window.eventsData).flat().forEach(ev => {
                        if (ev.event_id == eventId) originalEvent = ev;
                    });

                    // 如果找不到資料則不執行
                    if (!originalEvent) return;
                    const eventTitle = originalEvent.event_title || '未命名活動';
                    /* event_title end */

                    // --- 1. 生成驗證題目 ---
                    const n1 = Math.floor(Math.random() * 10) + 1;
                    const n2 = Math.floor(Math.random() * 10) + 1;
                    const ans = n1 + n2;

                    // --- 2. 彈出驗證視窗 ---
                    Swal.fire({
                        title: '確認移動活動？',
                        html: `您正試圖將 <b>[${eventTitle}]</b><br>移動至 <b>${newDate}</b><br><br>請輸入驗證計算結果：<br><b>${n1} + ${n2} = ?</b>`,
                        input: 'text',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '確定移動',
                        cancelButtonText: '取消',
                        allowOutsideClick: false, // 防止誤觸外面關閉
                        preConfirm: (value) => {
                            if (!value) {
                                Swal.showValidationMessage('請輸入計算結果');
                                return false;
                            }
                            if (parseInt(value) !== ans) {
                                Swal.showValidationMessage('計算錯誤，請重新確認');
                                return false;
                            }
                            return true;
                        }
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            // --- 使用者驗證成功：執行存檔 ---

                            // 獲取原始資料以組合時間 (如同之前的邏輯)
                            let originalEvent = null;
                            Object.values(window.eventsData).flat().forEach(ev => {
                                if (ev.event_id == eventId) originalEvent = ev;
                            });
                            if (!originalEvent) return;

                            const oldStartFull = moment(originalEvent.event_start_date);
                            const oldEndFull = originalEvent.event_end_date ? moment(originalEvent.event_end_date) : null;
                            const newStartStr = newDate + ' ' + oldStartFull.format('HH:mm:ss');
                            const newEndStr = oldEndFull ? newDate + ' ' + oldEndFull.format('HH:mm:ss') : '';

                            const formData = new URLSearchParams();
                            formData.append('event_id', eventId);
                            formData.append('event_start_date', newStartStr);
                            formData.append('event_end_date', newEndStr);
                            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);

                            try {
                                const response = await fetch('api/save_event.php', { method: 'POST', body: formData });
                                const res = await response.json();
                                if (res.rs == "1") {
                                    Swal.fire({ icon: 'success', title: '已移動', timer: 1000, showConfirmButton: false });
                                    initscal(ym);
                                } else {
                                    Swal.fire('錯誤', res.msg, 'error');
                                    initscal(ym);
                                }
                            } catch (e) {
                                console.error(e);
                                initscal(ym);
                            }
                        } else {
                            // --- 使用者取消或關閉視窗：將活動彈回原位 ---
                            initscal(ym);
                        }
                    });
                }


            }
        });
    });
}
