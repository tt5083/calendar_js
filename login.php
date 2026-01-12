<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="utf-8">
    <title>登入</title>
    <link href="css/appcsslib.css" rel="stylesheet">
    <link id="bs-css" href="css/bootstrap-cerulean.min.css" rel="stylesheet">
    <link href="css/web-app.css" rel="stylesheet">
    <!-- external javascript -->
    <script src="js/jqlib.js"></script>
    <!-- data table plugin -->
    <script src="js/jqdataTables.js"></script>
    <script src="js/tool.openWindow.js"></script>
    <script src="js/web-app.js"></script>
    <!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
            <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
    <!-- The fav icon -->
    <link rel="shortcut icon" href="img/favicon.ico">
    <style>
        .toggle-password-btn {
            height: 100%;
            scale: 1.2;
            padding: 8px 10px;
            padding-top: 10px;
            margin-left: 10px;
            margin-right: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card card-login mx-auto mt-5 col-md-6">
            <div class="card-header text-center">
                <h3 id="SysTitle">測試管理登入系統</h3>
            </div>
            <div class="card-body">
                <form id="flogin" data-parsley-validate="">
                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text">
                            <i class="bi bi-person-circle"></i>
                        </span>
                        <input type="text" id="u" name="u" class="form-control" placeholder="帳號" required autofocus>
                    </div>
                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="password" id="p" name="p" class="form-control" placeholder="密碼" required>
                        <span class="input-group-text" id="toggle-password" style="cursor: pointer;">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                    <div class="text-center">
                        <button type="submit" id="btnlogin" class="btn btn-primary btn-block">登入</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usernameInput = document.querySelector('#u');
            const passwordInput = document.querySelector('#p');

            usernameInput.focus();

            usernameInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    passwordInput.focus();
                }
            });
        });

        $(function() {
            $('#flogin').parsley({
                errorClass: 'is-invalid',
                successClass: 'is-valid',
                errorsWrapper: '<div class="invalid-feedback"></div>',
                errorTemplate: '<span></span>'
            });

            // 密碼顯示切換
            document.querySelector('#toggle-password').addEventListener('click', () => {
                const passwordField = document.querySelector('#p');
                const passwordFieldType = passwordField.getAttribute('type');
                const icon = document.querySelector('#toggle-password i');

                if (passwordFieldType === 'password') {
                    passwordField.setAttribute('type', 'text');
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    passwordField.setAttribute('type', 'password');
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });

            $('#flogin').submit(function(e) {
                e.preventDefault();
                if ($(this).parsley().isValid()) {
                    var lrs = ajaxPost("login_CL.php?act=loginst", $("#flogin").serialize());
                    if (lrs.rs != undefined && lrs.rs == "1") {
                        Swal.fire({
                            title: lrs.msg,
                            text: '成功',
                            icon: 'success',
                            timer: 1500
                        }).then(function() {
                            location.href = lrs.url;
                        });
                    } else {
                        Swal.fire({
                            title: lrs.msg,
                            text: '失敗',
                            icon: 'error'
                        });
                    }
                }
            });
        });
    </script>
</body>

</html>
