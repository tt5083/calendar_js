<?php

/**
 * Footer
 *
 * Main footer file for the theme.
 *
 * @category   Components
 * @package    WordPress
 * @subpackage Theme_Name_Here
 * @author     Your Name <yourname@example.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link       https://yoursite.com
 * @since      1.0.0
 */

/** 產生Javascript Alert訊息 */
function Alert($msg)
{
    echo '<script type="text/javascript">';
    echo 'window.alert("' . $msg . '");';
    echo '</script>';
}
//alert 訊息並導向網址
function Alerturl($msg, $url)
{
    echo '<script type="text/JavaScript">';
    echo 'window.alert("' . $msg . '");';
    echo 'location.href = "' . $url . '";';
    echo '</script>';
}


function status_deslab($n)
{
    $msg = "";
    switch ($n) {
        case 0:
            $msg = '<span class="btn btn-warning" disabled="disabled">停用</span>';
            break;
        case 1:
            $msg = '<span class="btn btn-success" disabled="disabled">啟用</span>';
            break;
        case 2:
            $msg = '<span class="btn btn-danger" disabled="disabled">刪除</span>';
            break;
    }
    echo $msg;
}

function sendPostData($u, $p)
{
    $url = AUTHURL;
    //The JSON data.
    $jsonData = array(
        'u' => $u,
        'p' => $p
    );
    //Encode the array into JSON.
    $jsonDataEncoded = json_encode($jsonData);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $result = curl_exec($ch);
    curl_close($ch);  // Seems like good practice
    return $result;
}

/** 取得使用者腳色 */
function getUserRoles($uid)
{
    $rs = "0";
    ${D} = db_connect();
    $sql_roleuser = "SELECT * FROM sys_role_users WHERE u_id = ? AND u_status = 1;";
    $roleuser = ${D}->{PR}($sql_roleuser);
    $roleuser->{EX}(array($uid));
    $roleuser_rows = $roleuser->{FT}();
    if ($roleuser->{RC}() != 0) {
        ${S}[SYSID . 'u_sn'] = $roleuser_rows['ru_sn'];;
        ${S}[SYSID . 'u_role'] = $roleuser_rows['u_role'];
        ${S}[SYSID . 'pfn'] = $roleuser_rows['personal_fnlist'];
        $rs = "1";
    } /*else {
        ${S}[SYSID . 'u_sn'] = 1;
        ${S}[SYSID . 'u_role'] = 1;
        $rs = "1";
    }*/
    return $rs;
}

/** 取得使用者機構/部門 */
function getUserDepartment($uid)
{
    $rs = "0";
    ${D} = db_connect();
    $sql_idp = "SELECT institution,department_number FROM member.vw_eidp WHERE employee_id = ? AND astatus = 'Y' AND main = 'Y';";
    $idp = ${D}->{PR}($sql_idp);
    $idp->{EX}(array($uid));
    $idp_rows = $idp->{FT}();
    if ($idp->{RC}() != 0) {
        ${S}[SYSID . 'inst'] = $idp_rows['institution'];
        ${S}[SYSID . 'depn'] = $idp_rows['department_number'];
        $rs = "1";
    }
    return $rs;
}

function eMsg($e, $msg)
{/*
    if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
        return $msg. '，請聯絡網站服務人員，謝謝！';
    }*/
    return $e->getMessage();
}

/** 取得顯示的值 */
// 特定value要轉換成給人看得name
function getDisplayValue($field, $val)
{
    if (in_array($field, ['ia_value', 'dc_value', 'ec_value', 'ea_value'])) {
        return getNameFromCode($field, $val);
    }
    return $val;
}

/** 根據欄位代碼(value)查中文名 */
function getNameFromCode($field, $value)
{
    if (!$value) return $value; // 空值直接回傳

    ${D} = db_connect();

    switch ($field) {
        case 'ia_value': // 收入科目
            $stmt = ${D}->{PR}("SELECT ea_name FROM expend_account WHERE ea_value = ?");
            break;
        case 'dc_value': // 支出門別
            $stmt = ${D}->{PR}("SELECT dc_name FROM door_class WHERE dc_value = ?");
            break;
        case 'ec_value': // 支出類別
            $stmt = ${D}->{PR}("SELECT ec_name FROM expend_class WHERE ec_value = ?");
            break;
        case 'ea_value': // 支出科目
            $stmt = ${D}->{PR}("SELECT ea_name FROM expend_account WHERE ea_value = ?");
            break;
        default:
            return $value;
    }

    $stmt->{EX}([$value]);
    $res = $stmt->{FT}(PDO::FETCH_ASSOC);
    return $res ? array_values($res)[0] : $value;
}

/** 從組裝好的sql語法取得欄位名稱=>更動值再放到array內 */
function parseSQL($sql, $db = null)
{
    $sql = trim($sql);
    $result = [
        'type' => null,     // insert/update/delete
        'table' => null,    // table名稱
        'newData' => [],    // 有被更動的資料array (name => val)
        'idColumn' => null, // 對應table的pk(id)
        'idValue' => null   // 被更動值料的pk(id) val
    ];

    // UPDATE
    if (preg_match('/UPDATE\s+`?(\w+)`?\s+SET\s+(.+?)\s+WHERE\s+`?(\w+)`?\s*=\s*(.+);?/i', $sql, $matches)) {
        $result['type'] = 'update';
        $result['table'] = $matches[1];
        $result['idColumn'] = $matches[3];
        $result['idValue'] = trim($matches[4], " '\";");

        // 解析 SET
        $setPairs = explode(',', $matches[2]);
        foreach ($setPairs as $pair) {
            list($col, $val) = explode('=', $pair, 2);
            $col = trim($col, " `");
            $val = trim($val, " '\"");
            $result['newData'][$col] = $val;
        }
        return $result;
    }

    // INSERT (有欄位)
    if (preg_match('/INSERT\s+INTO\s+`?(\w+)`?\s*\((.+?)\)\s*VALUES\s*\((.+?)\)/i', $sql, $matches)) {
        $result['type'] = 'insert';
        $result['table'] = $matches[1];
        $columns = array_map('trim', explode(',', $matches[2]));
        $values = array_map(function ($v) {
            return trim($v, " '\"");
        }, explode(',', $matches[3]));
        foreach ($columns as $i => $col) {
            $result['newData'][$col] = $values[$i] ?? null;
        }
        return $result;
    }

    // INSERT (無欄位)
    if (preg_match('/INSERT\s+INTO\s+`?(\w+)`?\s+VALUE[S]?\s*\((.+)\)/i', $sql, $matches)) {
        $result['type'] = 'insert';
        $result['table'] = $matches[1];
        $values = array_map('trim', explode(',', $matches[2]));
        // 從資料庫取得欄位順序
        $stmt = $db->{PR}("DESCRIBE $matches[1]");
        $stmt->{EX}();
        $columns = [];
        while ($row = $stmt->{FT}(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        foreach ($columns as $i => $col) {
            $result['newData'][$col] = $values[$i] ?? null;
        }
        return $result;
    }

    // DELETE
    if (preg_match('/DELETE\s+FROM\s+`?(\w+)`?\s+WHERE\s+`?(\w+)`?\s*=\s*(.+);?/i', $sql, $matches)) {
        $result['type'] = 'delete';
        $result['table'] = $matches[1];
        $result['idColumn'] = $matches[2];
        $result['idValue'] = trim($matches[3], " '\";");
        return $result;
    }

    return false;
}

/**
 * 紀錄歷史紀錄(給user看的)
 * 
 * @param {associative array} fieldMap - 關聯陣列，用來儲存需要被記錄的值，以及其英中對照表
 * @param {string} sql - sql語法，此處可放任意合法sql，後續由combinationSQL()以參數組裝成實際執行sql
 * @param {array} params - sql的參數
 * @param {string} note - 非必要，通常為insert時附帶的，用來方便資料庫change_log能快速對應此insert的資料的id
 * 機構碼與部門碼直接使用系統參數${S}[SYSID.'inst']與${S}[SYSID.'depn']，
 * 登入時會先取得
 */
function recordHistoryFromSQL($fieldMap, $sql, $params, $note = "")
{
    ${D} = db_connect();
    try {
        ${D}->beginTransaction();
        $sql = combinationSQL($sql, $params);
        // 解析 SQL
        $parsed = parseSQL($sql, ${D});
        if (!$parsed) throw new Exception("分析歷史紀錄失敗，請聯絡網站服務人員，謝謝！");

        $type = $parsed['type'];
        $table = $parsed['table'];
        $description = '';
        $now = new DateTime("now", new DateTimeZone("Asia/Taipei"));
        $currentTime = $now->format("Y-m-d H:i:s");

        if ($type == 'update') {
            // 取舊資料比對
            $idColumn = $parsed['idColumn'];
            $idValue = $parsed['idValue'];
            $stmtOld = ${D}->{PR}("SELECT * FROM $table WHERE $idColumn = ?");
            $stmtOld->{EX}([$idValue]);
            $oldData = $stmtOld->{FT}(PDO::FETCH_ASSOC);
            $changes = [];
            foreach ($parsed['newData'] as $field => $newVal) {
                $oldVal = $oldData[$field] ?? null;
                $oldVal = getDisplayValue($field, $oldVal);
                $newVal = getDisplayValue($field, $newVal);
                if (isset($fieldMap[$field]) && $oldVal != $newVal) {
                    $label = $fieldMap[$field];
                    $changes[] = "{$label}修改為{$newVal}";
                }
            }

            if (!empty($changes)) { // 有實際變更
                $description = "修改資料=>" . implode("，", $changes);
            }
        } elseif ($type == 'insert') {
            // 目前$note存放 idCol => idVal 所以直接拿來用
            if (preg_match('/\((\w+)\s*=\s*(.+)\)/', $note, $m)) {
                $idColumn = $m[1];
                $idValue  = $m[2];
            } else {
                throw new Exception("寫入歷史紀錄失敗，請聯絡網站服務人員，謝謝！");
            }
            $cols = [];
            foreach ($parsed['newData'] as $field => $val) {
                // 只處理有對應 fieldMap 的欄位
                if (isset($fieldMap[$field])) {
                    $val = getDisplayValue($field, $val);
                    $label = $fieldMap[$field];
                    if (!empty($val)) {
                        $cols[] = "{$label}：{$val}";
                    }
                }
            }
            if (!empty($cols)) {
                $description = "新增資料=>" . implode("，", $cols);
            }
        } elseif ($type == 'delete') {
            $idColumn = $parsed['idColumn'];
            $idValue = $parsed['idValue'];
            $description = "刪除資料";
        }

        if (!empty($description)) { // 有想記錄的變動才會記錄到change_history
            $stmt = ${D}->{PR}("INSERT INTO change_history VALUES (null, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->{EX}([$table, $idValue, ${S}[SYSID . 'inst'], ${S}[SYSID . 'depn'], ${S}['employee_id'], $currentTime, $description]);
            if ($stmt->{RC}() == 0) {
                throw new Exception("寫入歷史紀錄失敗，請聯絡網站服務人員，謝謝！");
            }
        }
        $sql .= $note;
        $stmt = ${D}->{PR}("INSERT INTO change_log VALUES (null, ?, ?, ?, ?)");
        $stmt->{EX}(array(${S}['employee_id'], $currentTime, $table, $sql));
        if ($stmt->{RC}() == 0) {
            throw new Exception("寫入 change_log 失敗，請聯絡網站服務人員，謝謝！");
        }
        ${D}->commit();
    } catch (Exception $e) {
        // 把錯誤往外拋，讓外層 try...catch 捕捉
        ${D}->rollBack();
        throw $e;
    }
}

/**
 * 多項歷史紀錄(給user看的)
 * 
 * @param {associative array} fieldMap - 關聯陣列，用來儲存需要被記錄的值，以及其英中對照表
 * @param {string} mainTable - 主表名稱，方便細項資料能快速對應到他的主表，使後續取用只要知道主表id即可快速查詢
 * @param {string} mainTableIdVal - 主表id
 * @param {array} entries - sql與parms的對應array
 * 機構碼與部門碼直接使用系統參數${S}[SYSID.'inst']與${S}[SYSID.'depn']，
 * 登入時會先取得
 */
function recordMutiHistoryFromSQL($fieldMap, $mainTable, $mainTableIdVal, $entries)
{
    ${D} = db_connect();
    try {
        ${D}->beginTransaction();
        $changeSQL = '';
        // 每筆係向使用同一個時間,表示是同一次更新
        $now = new DateTime("now", new DateTimeZone("Asia/Taipei"));
        $currentTime = $now->format("Y-m-d H:i:s");

        foreach ($entries as $entry) {
            $sql = combinationSQL($entry['sql'], $entry['params']);
            $changeSQL .= $sql . ";\n";
            $parsed = parseSQL($sql, ${D});
            if (!$parsed) throw new Exception("分析歷史紀錄失敗，請聯絡網站服務人員，謝謝！");

            $type = $parsed['type'];
            $table = $parsed['table'];
            $description = '';

            if ($type == 'update') {
                $idColumn = $parsed['idColumn'];
                $idValue = $parsed['idValue'];
                $stmtOld = ${D}->{PR}("SELECT * FROM $table WHERE $idColumn = ?");
                $stmtOld->{EX}([$idValue]);
                $oldData = $stmtOld->{FT}(PDO::FETCH_ASSOC);
                $changes = [];
                foreach ($parsed['newData'] as $field => $newVal) {
                    $oldVal = $oldData[$field] ?? null;
                    if ($oldVal != $newVal) {
                        $oldVal = getDisplayValue($field, $oldVal);
                        $newVal = getDisplayValue($field, $newVal);
                        $label = $fieldMap[$field] ?? $field;
                        $changes[] = "{$label}修改為{$newVal}";
                    }
                }
                if (empty($changes)) continue; // 無實際變更
                $description = "修改資料(id={$idValue})=>" . implode("，", $changes);
            } elseif ($type == 'insert') {
                $cols = [];
                foreach ($parsed['newData'] as $field => $val) {
                    if (isset($fieldMap[$field])) {
                        $val = getDisplayValue($field, $val);
                        $label = $fieldMap[$field];
                        $cols[] = "{$label}：{$val}";
                    }
                }
                if (!empty($cols)) {
                    $description = "新增資料(id={$entry['id']})=>" . implode("，", $cols);
                }
            } elseif ($type == 'delete') {
                $description = "刪除資料(id={$parsed['idValue']})";
            }
            if (!empty($description)) { // 有想記錄的變動才會記錄到change_history
                $stmt = ${D}->{PR}("INSERT INTO change_history VALUES (null, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->{EX}([$mainTable, $mainTableIdVal, ${S}[SYSID . 'inst'], ${S}[SYSID . 'depn'], ${S}['employee_id'], $currentTime, $description]);
                if ($stmt->{RC}() == 0) throw new Exception("寫入歷史紀錄失敗，請聯絡網站服務人員，謝謝！");
            }
        }
        if ($changeSQL != '') {
            $stmt = ${D}->{PR}("INSERT INTO change_log VALUES (null, ?, ?, ?, ?)");
            $stmt->{EX}([${S}['employee_id'], $currentTime, $table, $changeSQL]);
            if ($stmt->{RC}() == 0) throw new Exception("寫入 change_log 失敗，請聯絡網站服務人員，謝謝！");
        }

        ${D}->commit();
    } catch (Exception $e) {
        ${D}->rollBack();
        throw $e;
    }
}

// 組合 sql與參數
function combinationSQL($sql, $params)
{
    // 將多空/換行等等替換成單空
    $sql = preg_replace('/\s+/', ' ', $sql);
    // 先替換 NOW()
    $sql = str_replace("NOW()", "'" . date("Y-m-d H:i:s") . "'", $sql);

    if (array_keys($params) === range(0, count($params) - 1)) {
        // 處理 ? 佔位符（如果是索引陣列）
        foreach ($params as $param) {
            // 避免數字和 NULL 被加上引號
            $nParam = is_numeric($param) && !is_null($param) ? $param : "'$param'";
            $sql = preg_replace('/\?/', $nParam, $sql, 1);
        }
    } else {
        // 處理命名參數
        foreach ($params as $key => $value) {
            $nParam = is_numeric($value) && !is_null($value) ? $value : "'$value'";
            $sql = str_replace(":$key", $nParam, $sql);
        }
    }
    return $sql;
}

// 異動紀錄
function changeLog($sql, $params = null, $note = "")
{
    ${D} = db_connect();
    //搜尋table name
    preg_match('/\b(?:INSERT\s+INTO|DELETE\s+FROM|UPDATE|FROM|JOIN)\s+`?(\w+)`?/i', $sql, $matches);
    if (!empty($params)) {
        $sql = combinationSQL($sql, $params);
    }
    $sql .= $note;
    // 記錄異動到 change_log
    $now = new DateTime("now", new DateTimeZone("Asia/Taipei"));
    $currentTime = $now->format("Y-m-d H:i:s");
    try {
        ${D}->beginTransaction();
        $stmt = ${D}->{PR}("INSERT INTO change_log VALUES (null, ?, ?, ?, ?)");
        $stmt->{EX}(array(${S}['employee_id'], $currentTime, $matches[1], $sql));
        if ($stmt->{RC}() == 0) {
            throw new Exception("寫入 change_log 失敗，請聯絡網站服務人員，謝謝！");
        }
        ${D}->commit();
    } catch (Exception $e) {
        // 把錯誤往外拋，讓外層 try...catch 捕捉
        ${D}->rollBack();
        throw $e;
    }
}

function createSettingCode($prefix, $tableName, $columnName)
{
    $D = db_connect();

    // 計算 prefix 長度，從第 N 個字元開始取數字部分
    $prefix_len = strlen($prefix);

    // SQL：取出 prefix 後數字部分的最大值
    $sql = "
        SELECT MAX(CAST(SUBSTRING($columnName, " . ($prefix_len + 1) . ") AS UNSIGNED)) AS max_num
        FROM $tableName
        WHERE $columnName LIKE '{$prefix}%'
    ";

    $stmt = $D->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // 計算下一個編碼
    if ($result && $result['max_num'] !== null) {
        $next_number = (int)$result['max_num'] + 1;
    } else {
        $next_number = 1; // 若沒有資料，從 1 開始
    }

    $new_number = $prefix . $next_number;
    return $new_number;
}


function budgetPermCheck()
{
    $u_role = ${S}[SYSID . 'u_role'];
    //$u_role = "1";
    $canAdd = false;
    $canLock = false;

    switch ($u_role) {
        case finance:
            $canAdd = true;
            break;
        case budget_locker:
            $canLock = true;
            break;
        case admin:
            $canAdd = true;
            $canLock = true;
            break;
    }

    return [
        "canAdd" => $canAdd,
        "canLock" => $canLock
    ];
}

function getUniqueNumber($prefix)
{
    date_default_timezone_set('Asia/Taipei');
    $now_year = date("Y") - 1911;
    $timestamp = date("mdHis"); // 月日時分秒
    $micro = substr((string)microtime(true), 11, 3); // 取毫秒

    $uniqueNumber = $prefix . $now_year . $timestamp . $micro;

    return $uniqueNumber;
}

function getProjectCode()
{
    ${D} = db_connect();
    $now_year = date("Y") - 1911;
    $now_month = date("m");
    $prefix = $now_year . $now_month;
    // 構建查詢語句
    $sql = "SELECT MAX(pm_code) AS max_code
            FROM project_management
            WHERE pm_code LIKE :prefix";

    // 執行查詢
    $stmt = ${D}->{PR}($sql);
    $stmt->{EX}([
        ":prefix" => $prefix . "%"
    ]);
    $row = $stmt->{FT}(PDO::FETCH_ASSOC);
    $maxCode = $row['max_code'] ?? null;

    if ($maxCode) {
        // +1
        $pm_code = str_pad(((int)$maxCode + 1), strlen($maxCode), "0", STR_PAD_LEFT);
    } else {
        // 若本月沒有任何資料，從 民國年月0001 開始
        $pm_code = $prefix . "0001";
    }

    return $pm_code;
}


/**
 * 將 expend_class/expend_account 資料依 institution 分組
 *
 * @param array $data 原始資料
 * @return array [groupedEc, groupedEa]
 */
function groupByInst(array $data): array
{
    $groupedEc = [];
    $groupedEa = [];
    $seenEc = []; // 記錄每個機構已加入的 ec_value

    foreach ($data as $row) {
        $key = $row['institution'] ?? $row['pm_number'] ?? '';
        $ec_value = $row['ec_value'];
        $ec_name  = $row['ec_name'];
        $ea_value = $row['ea_value'];

        if (!isset($groupedEc[$key])) {
            $groupedEc[$key] = [];
            $seenEc[$key] = [];
        }

        // 分組 ec_value，只保留一次
        if ($ec_value !== null && !isset($seenEc[$key][$ec_value])) {
            $groupedEc[$key][] = [
                'ec_value' => $ec_value,
                'ec_name'  => $ec_name,
            ];
            $seenEc[$key][$ec_value] = true;
        }

        // 分組 ea_value
        if ($ea_value !== null) {
            if (!isset($groupedEa[$key])) {
                $groupedEa[$key] = [];
            }
            $groupedEa[$key][] = [
                'ec_value' => $ec_value,
                'ea_value' => $ea_value,
                'ea_name'  => $row['ea_name'] ?? null,
                'ea_desc'  => $row['ea_desc'] ?? null,
            ];
        }
    }

    return [$groupedEc, $groupedEa];
}
