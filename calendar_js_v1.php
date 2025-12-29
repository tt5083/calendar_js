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
        var htmls = CreateCal(ym); // 來自 web-app.js
        $("#tb").html(htmls);

        // ===== 範例事件資料（正式上線請改成從後端動態取得）=====
        let cal = [
            {
                "2025-12-29": [
                    { "even": "aaaa" },
                    { "even": "bbb" },
                    { "even": "ccc" }
                ]
            },
            {
                "2025-12-30": [
                    { "even": "qqq" },
                    { "even": "ww" }
                ]
            }
        ];

        // 清空所有日期的事件（避免切月份時殘留舊資料）
        $(".calendar_cell span").html("");

        // 填入事件
        $.each(cal, function(index, vals) {
            $.each(vals, function(dateKey, events) {
                var dvs = "";
                $.each(events, function(i, event) {
                    dvs += "<span>" + event.even + "</span><br>";
                });
                $("#m_" + dateKey).html(dvs);
            });
        });
    }
</script>

</html>
