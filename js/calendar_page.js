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
let currentView = "month"; 
let currentWeekStart = moment().startOf('week');

/* --- 3. 頁面初始化與監聽 --- */
document.addEventListener('DOMContentLoaded', function () {
    initscal(ym);

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

    // 地點過濾
    document.getElementById('locationFilter')?.addEventListener('change', function () {
        filterLocation = this.value;
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
    document.getElementById('btnEditInView')?.addEventListener('click', function() {
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

    document.getElementById('btnDeleteInView')?.addEventListener('click', async function() {
        if (!currentViewEvent) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (confirm(`您確定要刪除「${currentViewEvent.event_title}」嗎？`)) {
            const formData = new URLSearchParams();
            formData.append('event_id', currentViewEvent.event_id);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('api/delete_event.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.rs == "1") {
                hideModal('viewEventModal');
                // 這裡假設您有引用 sweetalert，若無可改回 alert
                if (typeof showSwal === 'function') {
                    showSwal("刪除成功", true, "", () => initscal(ym));
                } else {
                    alert("刪除成功");
                    initscal(ym);
                }
            }
        }
    });
});

/* --- 4. 核心渲染流程 --- */

async function initscal(InYM) {
    ym = InYM;
    try {
        const response = await fetch(`api/get_events.php?ym=${InYM}`);
        eventsData = await response.json();
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
    const weekNames = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'];
    let days = [];
    let allFilteredNotes = [];

    for (let i = 0; i < 7; i++) {
        const currentDay = currentWeekStart.clone().add(i, 'days');
        const dateStr = currentDay.format("YYYY-MM-DD");
        const dayEvents = eventsData[dateStr] || [];
        let filtered = processEvents(dayEvents, dateStr, todayStr, allFilteredNotes);

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

/* --- 5. Modal 控制 (原生 BS5 API) --- */
function openAddModal(date) {
    document.querySelector("#addEventModal .modal-title").textContent = "新增活動";
    const form = document.getElementById('addEventForm');
    if (form) form.reset();
    document.getElementById("event_id_input").value = "";
    document.querySelector("input[name='event_start_date']").value = date + "T08:00";
    document.querySelector("input[name='event_end_date']").value = date + "T17:00";
    
    showModal('addEventModal');
}

function viewEventDetail(date, index) {
    if (!eventsData[date] || !eventsData[date][index]) return;
    const eventData = eventsData[date][index];
    currentViewEvent = eventData;

    const source = document.getElementById("event-detail-template").innerHTML;
    const template = Handlebars.compile(source);
    
    const startTime = eventData.event_start_date ? moment(eventData.event_start_date).format('YYYY-MM-DD HH:mm') : '';
    const endTime = eventData.event_end_date ? moment(eventData.event_end_date).format('YYYY-MM-DD HH:mm') : '';

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
    document.querySelectorAll(".btn-group .btn").forEach(btn => btn.classList.remove("active"));
    activeBtn.classList.add("active");
}

// 封裝 BS5 Modal 原生呼叫，防止 ARIA 衝突
function showModal(id) {
    const el = document.getElementById(id);
    if (el) {
        const inst = bootstrap.Modal.getOrCreateInstance(el);
        inst.show();
    }
}

function hideModal(id) {
    const el = document.getElementById(id);
    if (el) {
        const inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
    }
}
