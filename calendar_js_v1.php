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
    <link rel="shortcut icon" href="img/favicon.ico">
</head>

<body>
    <div id="tb">
    </div>
</body>
<script>
    // 全域變數：目前顯示的年月
    var ym = "2025-12";

    // 頁面載入完成後，先初始化日曆
    $(document).ready(function() {
        initscal(ym);

        // ===== 事件綁定：只寫一次，放在全域，使用事件委派 =====
        // 下一個月
        $(document).on("click", "#calNext", function() {
            var nowym = moment(ym + "-01").add(1, 'months').format("YYYY-MM");
            ym = nowym;
            console.log("Next: " + ym);
            initscal(ym);
        });

        // 上一個月
        $(document).on("click", "#calLast", function() {
            var nowym = moment(ym + "-01").subtract(1, 'months').format("YYYY-MM");
            ym = nowym;
            console.log("Last: " + ym);
            initscal(ym);
        });

        // 本月（今天所屬月份）
        $(document).on("click", "#calNow", function() {
            var nowym = moment().format("YYYY-MM");
            ym = nowym;
            console.log("Now: " + ym);
            initscal(ym);
        });
    });

    // ===== 日曆主函數 =====
    function initscal(ym) {
    var htmls = CreateCal(ym);      // 生成日曆表格結構
    $("#tb").html(htmls);

    // 先清空所有日期格子的事件內容（避免切換月份時殘留舊資料）
    $("[id^='m_']").html("");

    // 透過 AJAX 取得該月份的事件
    $.ajax({
        url: 'get_events.php',
        type: 'GET',
        data: { ym: ym },
        dataType: 'json',
        cache: false,  // 避免瀏覽器快取舊資料
        success: function(events) {
            // events 格式示例：
            // {
            //   "2025-12-03": [{"even": "公司會議"}, {"even": "生日聚會"}],
            //   "2025-12-07": [{"even": "出差"}]
            // }

            $.each(events, function(dateKey, eventList) {
                var dvs = "";
                $.each(eventList, function(i, event) {
                    dvs += "<span>" + event.even + "</span><br>";
                });
                // 填入對應的日期格子（假設您的格子 ID 格式為 m_YYYY-MM-DD）
                $("#m_" + dateKey).html(dvs);
            });
        },
        error: function(xhr, status, err) {
            console.error("載入事件失敗:", status, err);
            // 可選：顯示友善訊息給使用者
            // alert("無法載入事件，請稍後再試");
        }
    });
}
</script>

</html>
