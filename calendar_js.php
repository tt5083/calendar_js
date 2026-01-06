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
    <title>活動日曆 - 原生 JS 版</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/appcsslib.css" rel="stylesheet">
    <link href="css/web-app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.7.7/handlebars.min.js"></script>
    
    <script src="js/tool.openWindow.js"></script>
    <script src="js/calendar_page.js"></script>

    <link rel="shortcut icon" href="img/favicon.png">
    
    <style>
        /* 補充：確保日曆格子在原生 BS5 下的高度表現 */
        .calendar_cell {
            min-height: 120px;
            cursor: pointer;
            vertical-align: top;
        }
        .today-highlight { background-color: #fff9db !important; }
        .cell-empty { background-color: #f8f9fa; cursor: default; }
    </style>
</head>

<body>
    <div class='d-flex align-items-center justify-content-center my-4'>
        <button id='calLast' class='btn btn-outline-info btn-sm me-3'>
            <i class="fa-solid fa-chevron-left"></i> 上個月
        </button>
        <h3 class='mb-0 mx-2' style='font-weight: 600; min-width: 150px; text-align: center;' id="displayYM"></h3>
        <button id='calNext' class='btn btn-outline-info btn-sm ms-3'>
            下個月 <i class="fa-solid fa-chevron-right"></i>
        </button>
        <button id='calNow' class='btn btn-secondary btn-sm ms-3'>本月</button>

        <div class="ms-3 d-inline-block">
            <select id="locationFilter" class="form-select form-select-sm" style="border-radius: 20px;">
                <option value="">所有地點</option>
                <option value="醫院">醫院</option>
                <option value="園區">園區</option>
                <option value="水電">水電</option>
            </select>
        </div>
        <div class="btn-group ms-3">
            <button type="button" id="viewMonth" class="btn btn-outline-primary btn-sm active">月</button>
            <button type="button" id="viewWeek" class="btn btn-outline-primary btn-sm">週</button>
        </div>
    </div>

    <div id="tb" class="container-fluid"></div>

    <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
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
                                <input type="datetime-local" class="form-control border-primary" name="event_start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">結束時間</label>
                                <input type="datetime-local" class="form-control" name="event_end_date">
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

    <div class="modal fade" id="viewEventModal" tabindex="-1" aria-labelledby="viewEventModalLabel" aria-hidden="true">
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

    <script id="calendar-template" type="text/x-handlebars-template">
        <table class='table table-bordered align-middle'>
            <thead class="table-light">
                <tr>
                    <th class="text-center text-danger">日</th>
                    <th class="text-center">一</th>
                    <th class="text-center">二</th>
                    <th class="text-center">三</th>
                    <th class="text-center">四</th>
                    <th class="text-center">五</th>
                    <th class="text-center text-primary">六</th>
                </tr>
            </thead>
            <tbody>
                {{#each rows}}
                <tr>
                    {{#each cells}}
                    <td class="calendar_cell {{#if isToday}}today-highlight{{/if}} {{#unless isMainMonth}}cell-empty{{/unless}}" 
                        data-date="{{date}}" 
                        {{#if isMainMonth}}onclick="openAddModal('{{date}}')"{{/if}}>
                        <div class="fw-bold date-label mb-1">
                            {{#if isToday}}<span class="badge rounded-pill bg-danger">{{dayNum}}</span>{{else}}{{dayNum}}{{/if}}
                        </div>
                        <div class="event-container">
                            {{#each filteredEvents}}
                            <div class="event-item-box p-1 mb-1 small border rounded bg-white shadow-sm" 
                                 onclick="event.stopPropagation(); viewEventDetail('{{../date}}', {{originalIndex}})">
                                <div class="text-muted fw-bold" style="font-size: 0.75rem;">{{startTime}}-{{endTime}}</div>
                                <div class="text-truncate">{{event_title}}</div>
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
                                    <span class="note-text-content small">{{event_note}}</span>
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
        <table class='table table-bordered'>
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
                    <td class="calendar_cell {{#if isToday}}today-highlight{{/if}}" 
                        data-date="{{date}}" onclick="openAddModal('{{date}}')">
                        <div class="event-container">
                            {{#each filteredEvents}}
                            <div class="event-item-box p-1 mb-1 small border rounded bg-white shadow-sm" 
                                 onclick="event.stopPropagation(); viewEventDetail('{{../date}}', {{originalIndex}})">
                                <div class="fw-bold text-primary">{{startTime}}</div>
                                <div class="text-wrap">{{event_title}}</div>
                            </div>
                            {{/each}}
                        </div>
                    </td>
                    {{/each}}
                </tr>
            </tbody>
            <tfoot class="month-notes-footer bg-light">
                <tr>
                    <td class="fw-bold text-center py-3">當週備註</td>
                    <td colspan="7" class="p-2">
                        {{#if notes.length}}
                            {{#each notes}}
                            <div class="note-card-row mb-1 {{#if isToday}}text-primary fw-bold{{/if}}">
                                <span class="badge bg-info me-2">{{shortDate}}</span>
                                <span class="small">{{event_note}}</span>
                            </div>
                            {{/each}}
                        {{else}}
                            <span class="text-muted small px-3">本週尚無活動備註。</span>
                        {{/if}}
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
