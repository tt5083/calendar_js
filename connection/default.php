<?php
require_once(__DIR__."/links.php");
require_once(__DIR__."/functions.php");

if (!isset(${S}['employee_id']) || empty(${S}['employee_id'])) {
    session_unset();
    session_destroy();
    ${C}["PHPSESSID"] = '';
    header("Location:login.php");
    exit;
} else {
    if (!isset(${S}[SYSID.'u_role'])) {
        $rsr = getUserRoles(${S}['employee_id']);
    }
}

if (empty(${S}[SYSID.'inst']) || empty(${S}[SYSID.'depn'])) {
    getUserDepartment(${S}['employee_id']);
}

/** 檢查權限 */
function rolechk($urole, $funlv)
{
    if ($urole!=$funlv) {
        Alerturl('你沒有操作此功能的權限，請聯繫管理員', 'index.php');
    }
}

/** 
 * 檢查權限2 
 * @param  string $urole 腳色
 * @param  string $funlv 使用功能
 * @return bool 回傳是否有權限
 */
function rolechkTF($urole, $funlv)
{
    $rs=false;
    if (in_array($urole, $funlv)) {
        $rs=true;
    }
    return $rs;
}

?>
