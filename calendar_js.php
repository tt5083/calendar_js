<?php
require_once __DIR__ . "/connection/default.php";
$allitem = 0;
$todatkeyin = 0;
$nokeyin = 0;
/* 'sys_header.php'; */
if (${S}[SYSID . 'pfn'] == 1) {
    $sql = "SELECT md_id,fn_id,fn_name,fn_url,fn_icon 
        FROM personal_fnlist a 
        INNER JOIN sys_function_list b ON a.fn_sn=b.fn_sn 
        WHERE employee_id = ? AND a.rf_status = 1 ORDER BY md_id,fn_id ";
    $rs = ${D}->{PR}($sql);
    $rs->{EX}(array(${S}['employee_id']));
} else {
    $sql = "SELECT md_id,fn_id,fn_name,fn_url,fn_icon 
        FROM sys_role_fnlist a 
        INNER JOIN sys_function_list b ON a.fn_sn=b.fn_sn 
        WHERE role_id = ? AND a.rf_status = 1 ORDER BY md_id,fn_id ";
    $rs = ${D}->{PR}($sql);
    $rs->{EX}(array(${S}[SYSID . 'u_role']));
}
$rows = $rs->{FA}(PDO::FETCH_ASSOC);
$NAVLIST = array();
$lastMid = "";
foreach ($rows as $mrow) {
    $mid = $mrow['md_id'];
    if ($lastMid != $mid && $mrow['fn_id'] == 0) {
        $NAVLIST[$mid] = array();
    }

    $NAVLIST[$mid][] = $mrow;
    $lastMid = $mid;
}
$icon = "";
/* sys header.php END */
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>活動日曆</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/appcsslib.css" rel="stylesheet">
    <link href="css/web-app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="js/jqlib.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="js/tool.openWindow.js"></script>
    <script src="js/calendar_page.js"></script>
    <script src="js/web-app.js"></script>
    
    <link rel="shortcut icon" href="img/favicon.png">
    <!-- 測試載入字體 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- 字體 END -->
</head>

<body>
    <!-- topbar starts -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark static-top">
        <a class="navbar-brand ps-3" href="index.php">
            <span id="SysTitle">日曆活動活理</span>
        </a>
        <!-- Navbar -->
        <ul class="navbar-nav ms-auto mr-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown no-arrow mx-1">
                <a class="nav-link dropdown-toggle" href="#" id="themeDropdown" role="button" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-dice-d20 fa-fw"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="themeDropdown" id="themedd">
                    <a class="dropdown-item" href="#" data-value="classic">Classic<span></span></a>
                    <a class="dropdown-item" href="#" data-value="cerulean">Cerulean<span></span></a>
                    <a class="dropdown-item" href="#" data-value="cyborg">Cyborg<span></span></a>
                    <a class="dropdown-item" href="#" data-value="darkly">Darkly<span></span></a>
                    <a class="dropdown-item" href="#" data-value="litera">Litera<span></span></a>
                    <a class="dropdown-item" href="#" data-value="lumen">Lumen<span></span></a>
                    <a class="dropdown-item" href="#" data-value="materia">Materia<span></span></a>
                    <a class="dropdown-item" href="#" data-value="minty">Minty<span></span></a>
                    <a class="dropdown-item" href="#" data-value="simplex">Simplex<span></span></a>
                    <a class="dropdown-item" href="#" data-value="slate">Slate<span></span></a>
                    <a class="dropdown-item" href="#" data-value="spacelab">Spacelab<span></span></a>
                    <a class="dropdown-item" href="#" data-value="solar">solar<span></span></a>
                    <a class="dropdown-item" href="#" data-value="united">United<span></span></a>
                    <a class="dropdown-item" href="#" data-value="yeti">Yeti<span></span></a>
                </div>
            </li>
            <li class="nav-item dropdown no-arrow mx-1">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user-circle fa-fw"></i> <?php echo ${S}['employee_name']; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="sys_users_chpwd.php">變更密碼</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" id="alogout">登出</a>

                </div>
            </li>
        </ul>
    </nav>
    <div class='container-fluid my-4'>
        <div class='d-flex flex-wrap align-items-center justify-content-center gap-2'>

            <div class="d-flex align-items-center justify-content-center">
                <button id='calLast' class='btn btn-outline-info btn-sm'>
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <h3 class='mb-0 mx-2' style='font-weight: 600; min-width: 120px; text-align: center; font-size: 1.25rem;' id="displayYM"></h3>

                <button id='calNext' class='btn btn-outline-info btn-sm'>
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button id='calNow' class='btn btn-secondary btn-sm'>本月</button>
                <div class="view-tabs">
                    <button type="button" id="viewMonth" data-view="month" class="btn active">月檢視</button>
                    <button type="button" id="viewWeek" data-view="week" class="btn">週檢視</button>
                </div>
            </div>
            <div class="filter-wrapper location-filter">
                <select id="locationFilter" class="form-select">
                    <option value="">所有地點</option>
                    <option value="醫院">醫院</option>
                    <option value="園區">園區</option>
                    <option value="水電">水電</option>
                </select>
            </div>

            <div class="filter-wrapper organizer-filter">
                <select id="filterOrganizer" class="form-select">
                    <option value="">所有承辦單位</option>
                    <option value="電工">電工</option>
                    <option value="加工">加工</option>
                    <option value="綜長機構">綜長機構</option>
                    <option value="台東">台東</option>
                </select>
            </div>
        </div>
    </div>

    <div id="tb" class="container-fluid"></div>

    <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addEventModalLabel"><i class="fa-solid fa-calendar-plus me-2"></i>活動編輯</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addEventForm">
                        <input type="hidden" id="event_id_input" name="event_id">
                        <div class="row mt-2">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">活動主題 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" name="event_title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">發佈人姓名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="event_publisher" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">地點</label>
                                <input type="text" class="form-control" name="event_location">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-primary">開始時間 <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control border-primary" name="event_start_date" onclick="this.showPicker()" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">結束時間</label>
                                <input type="datetime-local" class="form-control" name="event_end_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">類別 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="event_category" required>
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
                                <hr><label class="form-label">備註事項</label>
                                <textarea class="form-control" name="event_note" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary px-4" id="saveEventBtn">儲存活動資料</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewEventModal" tabindex="-1" aria-labelledby="viewEventModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewEventModalLabel"><i class="fa-solid fa-circle-info"></i> 活動詳情</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewEventContent"></div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="btnDeleteInView">刪除</button>
                    <div>
                        <button type="button" class="btn btn-primary" id="btnEditInView">編輯</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            $('title').html('*** 日曆活動管理 ***');
            $('#SysTitle').html('日曆活動管理');
            $('#alogout').on('click', function() {
                logout();
                return false;
            });
        });
    </script>

    <script id="calendar-template" type="text/x-handlebars-template">
        <table class='table table-bordered align-middle'>
            <thead class="table-light">
                <tr>
                    <th class="text-center text-danger">星期日</th>
                    <th class="text-center">星期一</th>
                    <th class="text-center">星期二</th>
                    <th class="text-center">星期三</th>
                    <th class="text-center">星期四</th>
                    <th class="text-center">星期五</th>
                    <th class="text-center text-primary">星期六</th>
                </tr>
            </thead>
            <tbody>
                {{#each rows}}
                <tr>
                    {{#each cells}}
                    <td class="calendar_cell {{#if isToday}}today-highlight{{/if}} {{#unless isMainMonth}}cell-empty{{/unless}}" 
                        data-date="{{date}}">
                        <div class="fw-bold date-label mb-1">
                            {{#if isToday}}
                                <span class="badge rounded-pill bg-danger" {{#if isMainMonth}}onclick="event.stopPropagation(); openAddModal('{{date}}')" style="cursor: pointer;"{{/if}}>{{dayNum}}</span>
                            {{else}}
                                <span {{#if isMainMonth}}onclick="event.stopPropagation(); openAddModal('{{date}}')" style="cursor: pointer;"{{/if}}>{{dayNum}}</span>
                            {{/if}}
                        </div>
                        {{#if isMainMonth}}
                        <div class="add-event-btn" onclick="event.stopPropagation(); openAddModal('{{date}}')">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        {{/if}}
                        <div class="event-container">
                            {{#each filteredEvents}}
                            <div class="event-item-box p-1 mb-1 small border rounded bg-white shadow-sm" data-id="{{event_id}}"
                                 onclick="event.stopPropagation(); viewEventDetail('{{../date}}', {{originalIndex}})">
                                <div class="text-muted fw-bold" style="font-size: 0.75rem;">{{startTime}}-{{endTime}}</div>
                                <div class="text-wrap">{{event_title}}({{event_implementer}}{{event_location}})</div>
                            </div>
                            {{/each}}
                        </div>
                    </td>
                    {{/each}}
                </tr>
                {{/each}}
            </tbody>
            <tfoot class="month-notes-footer bg-light">
                <tr>
                    <td colspan="1" class="fw-bold text-center py-3">當月備註</td>
                    <td colspan="6" class="p-2">
                        <div id="month-notes-summary">
                            {{#if notes.length}}
                                {{#each notes}}
                                <div class="note-card-row mb-1 {{#if isToday}}text-primary fw-bold{{/if}}">
                                    <span class="badge bg-secondary me-2">{{shortDate}}</span>
                                    <span class="note-text-content">{{event_note}}</span>
                                </div>
                                {{/each}}
                            {{else}}
                                <span class="text-muted small px-3">本月尚無活動備註。</span>
                            {{/if}}
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </script>

    <script id="week-template" type="text/x-handlebars-template">
        <table class="table table-bordered calendar-table week-view-table mb-0">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 100px;">時間</th>
                    {{#each days}}
                    <th class="{{#if isToday}}table-warning{{/if}}">{{dayName}}<br><small class="text-muted">{{date}}</small></th>
                    {{/each}}
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center bg-light fw-bold">全天活動</td>
                    {{#each days}}
                    <td class="calendar_cell {{#if isToday}}today-highlight{{/if}}" data-date="{{date}}" onclick="openAddModal('{{date}}')">
                        <div class="event-container">
                            {{#each filteredEvents}}
                            <div class="event-item-box p-1 mb-1 small border rounded bg-white shadow-sm" data-id="{{event_id}}"
                                 onclick="event.stopPropagation(); viewEventDetail('{{../date}}', {{originalIndex}})">
                                <div class="fw-bold text-primary">{{startTime}}</div>
                                <div class="text-wrap">{{event_title}}({{event_implementer}}{{event_location}})</div>
                            </div>
                            {{/each}}
                        </div>
                    </td>
                    {{/each}}
                </tr>
            </tbody>
            <tfoot class="month-notes-footer bg-light">
                <tr>
                    <td colspan="1" class="fw-bold text-center py-3">當週備註</td>
                    <td colspan="6" class="p-2">
                        <div id="month-notes-summary">
                            {{#if notes.length}}
                                {{#each notes}}
                                <div class="note-card-row mb-1 {{#if isToday}}text-primary fw-bold{{/if}}">
                                    <span class="badge bg-secondary me-2">{{shortDate}}</span>
                                    <span class="note-text-content">{{event_note}}</span>
                                </div>
                                {{/each}}
                            {{else}}
                                <span class="text-muted small px-3">本週尚無活動備註。</span>
                            {{/if}}
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </script>

    <script id="event-detail-template" type="text/x-handlebars-template">
        <div class="list-group list-group-flush">
            {{#each fields}}
            <div class="list-group-item d-flex align-items-center py-3">
                <div class="fw-bold text-muted me-3" style="width: 120px; flex-shrink: 0;">{{label}}</div>
                <div class="flex-grow-1">
                    {{#if isHtml}}{{{value}}}{{else}}{{value}}{{/if}}
                </div>
            </div>
            {{/each}}
        </div>
    </script>
</body>

</html>
