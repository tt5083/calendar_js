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
    <title>活動日曆</title>
    <link href="css/appcsslib.css" rel="stylesheet">
    <link id="bs-css" href="css/bootstrap-cerulean.min.css" rel="stylesheet">
    <link href="css/web-app.css" rel="stylesheet">

    <script src="js/jqlib.js"></script>
    <script src="js/calendar_page.js"></script>
    <script src="js/jqdataTables.js"></script>
    <script src="js/tool.openWindow.js"></script>
    <script src="js/web-app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.7.7/handlebars.min.js"></script>

    <link rel="shortcut icon" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class='d-flex align-items-center justify-content-center my-4'>
        <button id='calLast' class='btn btn-outline-info btn-sm me-3'><i class="fa-solid fa-chevron-left"></i> 上個月</button>
        <h3 class='mb-0 mx-2' style='font-weight: 600; min-width: 150px; text-align: center;' id="displayYM"></h3>
        <button id='calNext' class='btn btn-outline-info btn-sm ms-3'>下個月 <i class="fa-solid fa-chevron-right"></i></button>
        <button id='calNow' class='btn btn-secondary btn-sm ms-3'>本月</button>

        <div class="ms-3 d-inline-block">
            <select id="locationFilter" class="form-select form-select-sm" style="border-radius: 20px;">
                <option value="">所有地點</option>
                <option value="醫院">醫院</option>
                <option value="園區">園區</option>
                <option value="水電">水電</option>
            </select>
        </div>
    </div>

    <div id="tb" class="container-fluid"></div>

    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-calendar-plus me-2"></i>活動編輯</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

    <div class="modal fade" id="viewEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-circle-info"></i> 活動詳情</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
        <table class='table table-bordered'>
            <thead>
                <tr class="table-light">
                    <th>日</th><th>一</th><th>二</th><th>三</th><th>四</th><th>五</th><th>六</th>
                </tr>
            </thead>
            <tbody>
                {{#each rows}}
                <tr>
                    {{#each cells}}
                    <td class="calendar_cell {{#if isToday}}today-highlight{{/if}} {{#unless isMainMonth}}cell-empty{{/unless}}" 
                        data-date="{{date}}" {{#if isMainMonth}}onclick="openAddModal('{{date}}')"{{/if}}>
                        <div class="fw-bold date-label">
                            {{#if isToday}}<span class="today-circle">{{dayNum}}</span>{{else}}{{dayNum}}{{/if}}
                        </div>
                        <div class="mt-1 event-container">
                            {{#each filteredEvents}}
                            <div class="event-item-box" onclick="event.stopPropagation(); viewEventDetail('{{../date}}', {{originalIndex}})">
                                <div class="event-time-row">{{startTime}}-{{endTime}}</div>
                                <div class="event-name-row">{{event_title}}</div>
                            </div>
                            {{/each}}
                        </div>
                    </td>
                    {{/each}}
                </tr>
                {{/each}}
            </tbody>
            <tfoot class="month-notes-footer">
                <tr>
                    <td colspan="1" class="notes-label-cell">當月備註</td>
                    <td colspan="6" class="notes-content-cell">
                        <div id="month-notes-summary">
                            {{#if notes.length}}
                                {{#each notes}}
                                <div class="note-card-row {{#if isToday}}is-today-note{{/if}}">
                                    <div class="note-card-body">
                                        <span class="note-time-tag">{{shortDate}}</span> | 
                                        <span class="note-text-content">{{event_note}}</span>
                                    </div>
                                </div>
                                {{/each}}
                            {{else}}
                                <span class="text-muted small">本月尚無活動備註。</span>
                            {{/if}}
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </script>
</body>
<script id="event-detail-template" type="text/x-handlebars-template">
    <div class="container-fluid px-0">
            {{#each fields}}
            <div class="row g-0 border-bottom align-items-center">
                <div class="col-3 bg-light text-end py-2 px-3 fw-bold">{{label}}：</div>
                <div class="col-9 py-2 px-3">
                    {{#if isHtml}}{{{value}}}{{else}}{{value}}{{/if}}
                </div>
            </div>
            {{/each}}
        </div>
    </script>

</html>
