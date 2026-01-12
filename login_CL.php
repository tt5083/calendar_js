<?php
require_once(__DIR__ . "/connection/links.php");
require_once(__DIR__ . "/connection/functions.php");
//取得記錄
if (isset(${G}['act']) && !empty(${G}['act'])) {
    $rs = 0;
    $msg = '';
    $url = 'login.php';
    $rsf = array();
    switch (${G}['act']) {
        //登入
        case "login":
            if (!empty(${P}['u']) && !empty(${P}['p'])) {
                $u = strtoupper(${P}['u']);
                $p = md5(${P}['p']);
                ${R} = ${D}->{PR}("SELECT * FROM users WHERE u_id=? AND u_password=?");
                ${R}->{EX}(array($u, $p));
                if (${R}->{RC}() > 0) {
                    ${ROS} = ${R}->{FT}();
                    ${S}[SYSID . 'u_sn'] = ${ROS}['u_sn'];
                    ${S}['employee_id'] = ${ROS}['u_id'];
                    ${S}['employee_name'] = ${ROS}['u_name'];
                    ${S}[SYSID . 'u_role'] = ${ROS}['u_role'];
                    $rs = "1";
                    $msg = '登入成功！！';
                    $url = 'index.php';
                } else {
                    $msg = "登入失敗！ \r\n 請檢查是否帳號或密碼輸入錯誤！";
                }
            }
            $rsf = ["rs" => $rs, "msg" => $msg, "url" => $url];
            echo json_encode($rsf);
            break;

        //登入 院內
        case "loginst":
            //使用者帳號及密碼登入驗證-院內
            $data = [];
            if (!empty(${P}['u']) && !empty(${P}['p'])) {
                $u = strtoupper(${P}['u']);
                $p = md5(${P}['p']);
                //登入驗證，目前用醫院的資料表內容
                $result = sendPostData($u, $p);
                $res = json_decode($result, true);
                if (/*is_array($res) && */$res['login'] == 'y') {
                    ${S}['employee_id'] = $res['employee_id'];
                    ${S}['employee_name'] = $res['employee_name'];
                    $employee_id = strtoupper($res['employee_id']);
                    $rst = getUserRoles($employee_id);
                    if ($rst == "1") {
                        $rst = getUserDepartment($employee_id);
                        if ($rst == "1") {
                            $rs = "1";
                            $msg = '登入成功！！';
                            $url = 'calendar_js.php';
                            $data["id"] = $res['employee_id'];
                            $data["name"] = $res['employee_name'];
                        }
                    } else {
                        $msg = "登入失敗！ \r\n 請確認是否有登入權限！";
                    }
                } else {
                    $msg = "登入失敗！ \r\n 帳號或密碼錯誤！";
                }
            } else {
                $msg = "登入失敗！ \r\n 請檢查是否帳號或密碼輸入錯誤！";
            }
            $rsf = ["rs" => $rs, "msg" => $msg, "url" => $url, "data" => $data];
            echo json_encode($rsf);
            break;
        //登出
        case "logout":
            session_unset();
            session_destroy();
            $_COOKIE["PHPSESSID"] = '';
            $rs = "1";
            $msg = '～登出成功～';
            $rsf = ["rs" => $rs, "msg" => $msg, "url" => $url];
            echo json_encode($rsf);
            break;
    }
}
