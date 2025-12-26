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
    var ym = "2025-12"
    initscal(ym);

    function initscal(ym) {

        var htmls = CreateCal(ym); /* 運用js/web-app.js中的function[InYM]*/
        $("#tb").html(htmls) /* #將參數丟到ID:tb */

        let cal = [{
                "2025-12-03": [{
                        "even": "aaaa"
                    },
                    {
                        "even": "bbb"
                    },
                    {
                        "even": "ccc"
                    }
                ]
            },
            {
                "2025-12-07": [{
                        "even": "qqq"
                    },
                    {
                        "even": "ww"
                    }
                ]
            }
        ];

        $.each(cal, function(index, vals) {
            $.each(vals, function(i, v) {

                var dvs = "";
                $.each(v, function(i1, v1) {
                    dvs += "<span>" + v1.even + "</span><br>";
                    console.log(i1);
                    console.log(v1);
                });
                console.log(vals);

                $("#m_" + i).html(dvs);
            });

        });
        //下一個月
        $("#calNext").on("click", function() {
            var nowym = moment(ym + "-01").add(1, 'M').format("YYYY-MM");
            ym = nowym
            console.log("N:" + ym);
            initscal(ym);

        });
        //上一個月
        $("#calLast").on("click", function() {
            var nowym = moment(ym + "-01").subtract(1, 'M').format("YYYY-MM");
            ym = nowym
            console.log("L:" + ym);
            initscal(ym);
        });
    }
</script>

</html>
