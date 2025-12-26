function confirmMsg(message) {
    return confirm(message);
}

$(function () {
    $('title').html('*** 預算管理系統 ***');
    $('#SysTitle').html('預算管理系統');
    $('#alogout').on('click', function () {
        logout();
        return false;
    });
    switchTheme(currentTheme);

    // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
    $('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function (e) {
        if ($(window).width() > 768) {
            var e0 = e.originalEvent,
                delta = e0.wheelDelta || -e0.detail;
            this.scrollTop += (delta < 0 ? 1 : -1) * 30;
            e.preventDefault();
        }
    });

    $("#themedd a").on("click", function (e) {
        e.preventDefault();
        currentTheme = $(this).attr('data-value');
        Cookies.set('currentTheme', currentTheme, {
            expires: 365
        });
        switchTheme(currentTheme);
    });

    $.getJSON('js/datetimepicker-zh-tw.json', function (data) {
        moment.updateLocale('zh-tw', data);
    });
});

function ajaxPost(url, datas) {
    var rs = "";
    $.ajax({
        type: "post",
        url: url,
        data: datas,
        dataType: "json",
        async: false,
        success: function (result) {
            rs = result;
        },
        error: function (err) {
            console.log(err);
        }
    });
    return rs;
}

function ajaxGet(url) {
    var rs = "";
    $.ajax({
        type: "get",
        url: url,
        dataType: "json",
        async: false,
        success: function (result) {
            rs = result;
        },
        error: function (err) {
            console.log(err);
        }
    });
    return rs;
}

function logout() {
    var lout = ajaxGet("login_CL.php?act=logout");
    if (lout.rs != undefined && lout.rs == "1") {
        Swal.fire({
            title: lout.msg,
            text: '成功',
            icon: 'success',
            timer: 1500
        }).then(function () {
            location.href = lout.url;
        }, function () {
            location.href = lout.url;
        });
    }
}

//themes, change CSS with JS
//default theme(CSS) is cerulean, change it if needed
var defaultTheme = 'minty';

var currentTheme = Cookies.get('currentTheme') == null ? defaultTheme : Cookies.get('currentTheme');
var msie = navigator.userAgent.match(/msie/i);
$.browser = {};
$.browser.msie = {};

function switchTheme(themeName) {
    if (themeName == 'classic') {
        $('#bs-css').attr('href', 'bower_components/bootstrap/dist/css/bootstrap.min.css');
    } else {
        $('#bs-css').attr('href', 'css/bootstrap-' + themeName + '.min.css');
    }

    $("#themedd a span").html("");
    $('#themedd a[data-value=' + themeName + '] span').html('  <i class="fas fa-check"></i>');
}

//establish history variables
var History = window.History, // Note: We are using a capital H instead of a lower h
    State = History.getState(),
    $log = $('#log');

//bind to State Change
History.Adapter.bind(window, 'statechange', function () { // Note: We are using statechange instead of popstate
    var State = History.getState(); // Note: We are using History.getState() instead of event.state
    $.ajax({
        url: State.url,
        success: function (msg) {
            $('#content').html($(msg).find('#content').html());
            $('#loading').remove();
            $('#content').fadeIn();
            var newTitle = $(msg).filter('title').text();
            $('title').text(newTitle);
            //docReady();
        }
    });
});

//SB-ADMIN
// Scroll to top button appear
$(document).on('scroll', function () {
    var scrollDistance = $(this).scrollTop();
    if (scrollDistance > 100) {
        $('.scroll-to-top').fadeIn();
    } else {
        $('.scroll-to-top').fadeOut();
    }
});

// Smooth scrolling using jQuery easing
$(document).on('click', 'a.scroll-to-top', function (event) {
    var $anchor = $(this);
    $('html, body').stop().animate({
        scrollTop: ($($anchor.attr('href')).offset().top)
    }, 1000, 'easeInOutExpo');
    event.preventDefault();
});

//SB-ADMIN
//actmenuitem("fn_D_9_F_2");
function actmenuitem(itemid) {
    if (itemid != "") {
        var strs = itemid.split("_F")[0];
        var pid = strs.substring(5, strs.length);
        console.log(pid);
        $("#" + itemid).addClass("active");
        $('#collapseDIV_' + pid).collapse({
            toggle: true
        });
    }
}

//------------------------------handlebars-樣板工具-------------------------------
//just-handlebars-helpers 擴展包
//https://github.com/leapfrogtechnology/just-handlebars-helpers
H.registerHelpers(Handlebars);

Handlebars.registerHelper('compare', function (lvalue, operator, rvalue, options) {

    var operators, result;

    if (arguments.length < 3) {
        throw new Error("Handlerbars Helper 'compare' needs 2 parameters");
    }

    if (options === undefined) {
        options = rvalue;
        rvalue = operator;
        operator = "==";
    }

    operators = {
        '==': function (l, r) {
            return l == r;
        },
        '===': function (l, r) {
            return l === r;
        },
        '!=': function (l, r) {
            return l != r;
        },
        '!==': function (l, r) {
            return l !== r;
        },
        '<': function (l, r) {
            return l < r;
        },
        '>': function (l, r) {
            return l > r;
        },
        '<=': function (l, r) {
            return l <= r;
        },
        '>=': function (l, r) {
            return l >= r;
        },
        'typeof': function (l, r) {
            return typeof l == r;
        }
    };

    if (!operators[operator]) {
        throw new Error("Handlerbars Helper 'compare' doesn't know the operator " + operator);
    }

    result = operators[operator](lvalue, rvalue);

    if (result) {
        return options.fn(this);
    } else {
        return options.inverse(this);
    }

});
//Radio buttion checked
Handlebars.registerHelper('checked', function (value, test) {
    if (value == undefined) return '';
    return value == test ? 'checked' : '';
});
///dp json
Handlebars.registerHelper('json', function (context) {
    return JSON.stringify(context);
});

Handlebars.registerHelper('fors', function (n, block) {
    var accum = '';
    for (var i = 0; i < n; ++i)
        accum += block.fn(i);
    return accum;
});
//for(;;)
Handlebars.registerHelper('for', function (from, to, incr, block) {
    var accum = '';
    for (var i = from; i < to; i += incr)
        accum += block.fn(i);
    return accum;
});

Handlebars.registerHelper("inc", function (value, options) {
    return parseInt(value) + 1;
});

// 狀態按鈕
Handlebars.registerHelper('buttonStatus', function (r_status) {
    if (r_status == 0) {
        return new Handlebars.SafeString('<button class="btn btn-warning" disabled>停用</button>');
    } else if (r_status == 1) {
        return new Handlebars.SafeString('<button class="btn btn-success" disabled>啟用</button>');
    } else if (r_status == 2) {
        return new Handlebars.SafeString('<button class="btn btn-danger" disabled>刪除</button>');
    }
    showSwal("狀態讀入異常", false, "請聯絡網站服務人員！");
    return '';
});

// 收支類型
Handlebars.registerHelper('ieType', function (r_value) {
    if (r_value == 0) {
        return '收入';
    } else if (r_value == 1) {
        return '支出';
    }
    showSwal("收支類型讀入異常", false, "請聯絡網站服務人員！");
    return '';
});

Handlebars.registerHelper('yn', function (r_value) {
    if (r_value == 0) {
        return '否';
    } else if (r_value == 1) {
        return '是';
    }
    showSwal("讀入異常", false, "請聯絡網站服務人員！");
    return '';
});

Handlebars.registerHelper('addOne', function (value) {
    return parseInt(value) + 1;
});

//Font Awesome 的 <i> 標籤用於顯示圖標，使用 Handlebars 的 {{{ triple mustache }}} + 自訂 Helper（安全又靈活）
Handlebars.registerHelper('tempWithIcon', function(temp) {
    let colorClass = '';
    let icon = '';
    let textColor = 'white';

    if (temp > 32) {
        colorClass = 'temp-high';
        icon = '<i class="fas fa-fire text-danger me-1"></i>'; // 火 icon，紅色
    } else if (temp < 20) {
        colorClass = 'temp-low';
        icon = '<i class="fas fa-snowflake text-info me-1"></i>'; // 雪花，藍色
    } else {
        colorClass = 'temp-normal';
        icon = '<i class="fas fa-sun text-warning me-1"></i>'; // 太陽，黃色
    }

    // 返回完整 HTML，並用 Handlebars.SafeString 標記為安全（避免被自動 escape）
    return new Handlebars.SafeString(
        `<span class="${colorClass}">${icon}${temp}°C</span>`
    );
});
//------------------------------handlebars-樣板工具-------------------------------
//------------------------------Handlebars工具--------------------------------
/**
 * 
 * @param paths {string} 檔案路徑
 * @param tplid {string} 樣板來源ID
 * @param targetid {string} 顯示結果ID
 */
function gettpl(paths, tplid, targetid) {
    var source;
    var template;
    $.ajax({
        url: paths,
        cache: true
    }).done(function (data) {
        source = $(data).filter('#' + tplid).html();
        template = Handlebars.compile(source);
        $('#' + targetid).html(template);
    });
}

/**
 * 取得樣板，檔案 
 * @param paths {string} 檔案位置
 */
function hbstpl(paths) {
    var rs = "";
    var urls = paths + "?" + moment().format('mmss');
    $.ajax({
        url: urls,
        cache: true,
        async: false
    }).done(function (data) {
        rs = data;
    });
    return rs;
}

/**
 * 樣板資料合併
 * @param source {string} 樣板來源
 * @param data {json} 資料
 */
function hbstpldata(source, data) {
    var template;
    var html;
    template = Handlebars.compile(source);
    html = template(data);
    return html;
}

/**
 * 樣板呈現
 * @param {string} eid 元素ID
 * @param {string} hbsfile 樣板檔案位置
 * @param {json} data 資料
 */
function hbstohtml(eid, hbsfile, data) {
    var ptlhtml = hbstpldata(hbstpl(hbsfile), data);
    $("#" + eid).html(ptlhtml);
    sysinits();
}
//------------------------------Handlebars工具--------------------------------

function sysinits() {
    $(".parsleyf").parsley({
        errorClass: 'is-invalid text-danger',
        successClass: 'is-valid', // Comment this option if you don't want the field to become green when valid. Recommended in Google material design to prevent too many hints for user experience. Only report when a field is wrong.
        errorsWrapper: '<div class="invalid-feedback"></div>',
        errorTemplate: '<span></span>',
        trigger: 'change'
    });

    $('.select2').select2({
        theme: 'bootstrap',
        width: 'resolve'
    });

    // tooltip控制
    var $body = $('body');
    // 移除舊的事件避免重複
    $body.off('mouseenter', '.select2-tooltip-container');
    $body.off('mouseleave', '.select2-tooltip-container');

    // 委派事件
    $body.on('mouseenter', '.select2-tooltip-container', function () {
        var desc = $(this).data('desc');
        if (!desc) return;

        $('.select2-floating-tooltip').remove();

        var $tooltip = $('<div class="select2-floating-tooltip"></div>').text(desc).appendTo('body');

        $tooltip.addClass('show');

        var rect = $(this)[0].getBoundingClientRect();
        var tipW = $tooltip.outerWidth();
        var tipH = $tooltip.outerHeight();

        var top = rect.top - tipH - 8;
        var left = rect.left + rect.width / 2 - tipW / 2;

        if (left < 8) left = 8;
        if (left + tipW > window.innerWidth - 8)
            left = window.innerWidth - tipW - 8;

        if (top < 8)
            top = rect.bottom + 8;

        $tooltip.css({ top: top + window.scrollY, left: left + window.scrollX });
    }).on('mouseleave', '.select2-tooltip-container', function () {
        $('.select2-floating-tooltip').remove();
    });

    $(window).off('scroll.tooltip resize.tooltip');
    $(window).on('scroll.tooltip resize.tooltip', function () {
        $('.select2-floating-tooltip').remove();
    });


    //datatable
    $('.datatable').dataTable({
        "iDisplayLength": 25,
        "aLengthMenu": [
            [25, 50, 100, -1],
            [25, 50, 100, "All"]
        ],
        "stateSave": true,
        "sDom": "<'row'<'col-md-12 center-block'p><'col-md-6'li><'col-md-6 text-right'f>r>t<'row'<'col-md-12'i><'col-md-12 center-block'p>>",
        "sPaginationType": "full_numbers",
        "language": {
            "url": "js/Chinese-traditional.json"
        },
        "columnDefs": [{
            "targets": 'no-sort',
            "orderable": false,
        }]
    });

    $('.datatablewbtn').dataTable({
        "iDisplayLength": 25,
        "aLengthMenu": [
            [25, 50, 100, -1],
            [25, 50, 100, "All"]
        ],
        "buttons": [
            'copy', 'excel', 'pdf'
        ],
        "stateSave": true,
        "sDom": "<'row'<'col-md-12 center-block'pB><'col-md-6'li><'col-md-6 text-right'f>r>t<'row'<'col-md-12'i><'col-md-12 center-block'p>>",
        "sPaginationType": "full_numbers",
        "language": {
            "url": "js/Chinese-traditional.json"
        },
        "columnDefs": [{
            "targets": 'no-sort',
            "orderable": false,
        }]
    });
}

function initSelect2Tooltip(select, parentSelectId) {
    const $select = (typeof select === 'string') ? $('#' + select) : $(select);
    const $parentSelect = parentSelectId ? $('#' + parentSelectId) : null;
    
    $select.select2({
        theme: "bootstrap",
        width: '100%',
        escapeMarkup: m => m,
        templateResult: function (data) {
            if (!data.id) return data.text;

            const desc = $(data.element).data("desc");
            const name = data.text;

            if (desc) {
                return `
                    <span>${name}
                        <span class="select2-tooltip-container" data-desc="${desc}">
                            <i class="fas fa-info-circle"></i>
                        </span>
                    </span>
                `;
            }
            return name;
        },
        templateSelection: function (data) {
            return data.text;
        },
        matcher: function (params, data) {
            if ($parentSelect) {
                const classValue = $parentSelect.val();
                const ec = $(data.element).data("ec");

                // 無搜尋文字，且屬於當前類別
                if (!params.term && (!ec || ec == classValue)) return data;

                // 有搜尋文字，且匹配文字
                if (params.term && data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    if (!ec || ec == classValue) return data;
                }

                return null; // 不顯示
            } else {
                // 沒有父下拉時，僅依搜尋文字匹配
                if (!params.term || data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    return data;
                }
                return null;
            }
        }
    });

    $select.on('select2:select', function () {
        $('.select2-floating-tooltip').remove();
    });

    // 父下拉變更時清空子下拉
    if ($parentSelect) {
        $parentSelect.on('change', function () {
            $select.val(null).trigger('change');
        });
    }
}

//data to JSON
/**
 * 
 * @param {string} role data選擇參數
 * @param {json} jsonpk Json物件
 */
function d2j(role, jsonpk) {
    $("[data-tjson='" + role + "']").each(function () {
        var id = $(this).attr("id");
        var name = $(this).attr("name");
        var vals = (_.isNil($(this).val())) ? "" : $(this).val();
        var type = $(this).attr("type");
        if (type == "radio") {
            var vl = $('input[name=' + name + ']:checked').val();
            jsonpk[name] = (_.isNil(vl)) ? "" : vl;
        } else if (type == "checkbox") {
            if ($("input[name=" + name + "]").is(":checked")) {
                $('input:checkbox[name=' + name + ']:checked').each(function (k) {
                    if (k == 0) {
                        jsonpk[name] = (_.isNil($(this).val())) ? "" : $(this).val();
                    } else {
                        jsonpk[name] += ',' + (_.isNil($(this).val())) ? "" : $(this).val();
                    }
                });
            } else {
                jsonpk[name] = "";
            }
        } else {
            jsonpk[id] = vals;
        }
    });
    return jsonpk;
}
//------------------------------系統-框架工具--------------------------------
//------------------------------系統-JSON 處理--------------------------------
/* https://gist.github.com/iwek/3924925 */
/**
 * 
 * @param {*} obj 來源物件
 * @param {*} key 鍵
 * @param {*} val 值
 */
function getObjects(obj, key, val) {
    var objects = [];
    for (var i in obj) {
        if (!obj.hasOwnProperty(i)) continue;
        if (typeof obj[i] == 'object') {
            objects = objects.concat(getObjects(obj[i], key, val));
        } else
            //if key matches and value matches or if key matches and value is not passed (eliminating the case where key matches but passed value does not)
            if (i == key && obj[i] == val || i == key && val == '') { //
                objects.push(obj);
            } else if (obj[i] == val && key == '') {
            //only add if the object is not already in the array
            if (objects.lastIndexOf(obj) == -1) {
                objects.push(obj);
            }
        }
    }
    return objects;
}

//return an array of values that match on a certain key
/**
 * 依據鍵值取得對應物件 舉例：取出ID=1的JSON
 * @param {*} obj 來源物件
 * @param {*} key 鍵
 */
function getValues(obj, key) {
    var objects = [];
    for (var i in obj) {
        if (!obj.hasOwnProperty(i)) continue;
        if (typeof obj[i] == 'object') {
            objects = objects.concat(getValues(obj[i], key));
        } else if (i == key) {
            objects.push(obj[i]);
        }
    }
    return objects;
}

//return an array of keys that match on a certain value
/**
 * 依據數值取得對應物件 舉例：取出value=1的JSON
 * @param {*} obj 來源物件
 * @param {*} val 值
 */
function getKeys(obj, val) {
    var objects = [];
    for (var i in obj) {
        if (!obj.hasOwnProperty(i)) continue;
        if (typeof obj[i] == 'object') {
            objects = objects.concat(getKeys(obj[i], val));
        } else if (obj[i] == val) {
            objects.push(i);
        }
    }
    return objects;
}
//------------------------------系統-JSON 處理--------------------------------
//------------------------------系統-輔助工具--------------------------------
/**
 * 左邊補0
 * @param {string} str 字串
 * @param {number} lenght 長度
 */
function padLeft(str, lenght) {
    str = '' + str; //後來加的
    if (str.length >= lenght) {
        return str;
    } else {
        return padLeft("0" + str, lenght);
    }
}

/**
 * 右邊補0
 * @param {string} str 字串
 * @param {number} lenght 長度
 */
function padRight(str, lenght) {
    str = '' + str;
    if (str.length >= lenght) {
        return str;
    } else {
        return padRight(str + "0", lenght);
    }
}

//------------------------------系統-日曆(大)-S------------------------------
//建立
function CreateCal(InYM) {
    var i = 0;
    //初始化當月第一天
    var MFday = moment(InYM + "-01");
    //取出當月天數
    var lastday = moment(InYM, "YYYY-MM").daysInMonth();
    //初始化當月最後一天
    var MLday = moment(InYM + "-" + lastday);
    //第一天是星期幾(日為起始)
    var MFdayWeek = MFday.day();
    //最後一天是星期幾(日為起始)
    var MLdayWeek = MLday.day();
    //空白的日期
    var emptyString = "<td class='calendar_cell'> </td>";
    //建立表格
    var htmlstr = "";
    htmlstr += "<div class='row'><div class='col text-center'><button type='button' id='calLast' class='btn btn-info'>上個月</button></div><div class='col-8 text-center'><sapn id='calhead'>" + InYM + "</sapn>月</div><div class='col text-center'><button type='button' id='calNow' class='btn btn-light'>本月</button></div><div class='col text-center'><button type='button' id='calNext' class='btn btn-info'>下個月</button></div></div>";
    htmlstr += "<table class='table table-bordered table-striped table-hover mt-1'>";
    htmlstr += "<thead>";
    htmlstr +=
        "<tr><th class='text-center'>星期日</th><th class='text-center'>星期一</th><th class='text-center'>星期二</th><th class='text-center'>星期三</th><th class='text-center'>星期四</th><th class='text-center'>星期五</th><th class='text-center'>星期六</th></tr></thead>";
    //當月之前空格
    if (MFdayWeek > 0) {
        htmlstr += "<tr class='calendar_body'>";
        for (i = 0; i < MFdayWeek; i++) {
            htmlstr += emptyString;
        }
    }
    //當月
    for (i = 1; i <= lastday; i++) {
        var day = padLeft(i, 2); //按01 02 输出
        var clday = InYM + "-" + day;
        htmlstr += "<td class='calendar_cell text-center'>" + day;
        htmlstr += "<br><span id='m_" + clday + "'>";
        htmlstr += "</td>";
        //每周切換
        if ((i + MFdayWeek) % 7 == 0) {
            htmlstr += "</tr><tr class='calendar_body'>";
        }
    }
    //下一個月
    if (MLdayWeek > 0) {
        for (i = 0; i < 6 - MLdayWeek; i++) {
            htmlstr += emptyString;
        }
    }

    htmlstr += "</tr>";
    htmlstr += "</table>";
    //回傳結果
    return htmlstr;
}
//------------------------------系統-日曆(大)-E------------------------------

// 彈窗顯示
function showSwal(titleTxt, success, text = null, callback = null) {
    let ic = 'success';
    if (success == false) {
        ic = 'error';
    }
    Swal.fire({
        title: titleTxt,
        text: text,
        icon: ic,
        timer: 2000,
        confirmButtonText: "關閉",
        confirmButtonColor: "#674a96",
    }).then((result) => {
        if (callback && typeof callback === 'function') {
            callback();
        }
    });
}

function showFailSwal() {
    Swal.fire({
        title: '取得資料失敗',
        text: '請聯絡網站服務人員!',
        icon: 'error',
        timer: 2000,
        confirmButtonText: "關閉",
        confirmButtonColor: "#674a96",
    });
}


// 取得網址參數
function getParamValue(param) {
    let nowUrl = new URL(window.location);
    return nowUrl.searchParams.get(param) ?? "";
}

// 更新網址顯示的參數
// params可存在多個參數,以統一更新整個網址 
// 例如從分類跳為關鍵字查詢 要移除di加入search
// needPushState決定此次網址參數更新要不要新增一筆歷史紀錄
function updateUrlParams(params, needPushState = true) {
    let nowUrl = new URL(window.location);

    for (let key in params) {
        if (params[key] !== "" && params[key] !== "N") {
            nowUrl.searchParams.set(key, params[key]);
        } else {
            nowUrl.searchParams.delete(key);
        }
    }
    if (needPushState) {
        window.history.pushState({}, '', nowUrl);
    } else {
        window.history.replaceState({}, '', nowUrl);
    }
}

function toTop() {
    scrollTo(0, 0);
}

// 跳至前頁,使用參數來動態移除對應網址參數
function toPrvPage(params, callback) {
    let paramObj = {};
    if (Array.isArray(params)) {
        params.forEach(param => {
            paramObj[param] = "";
        });
    } else {
        paramObj[params] = "";
    }
    updateUrlParams(paramObj);
    callback();
}



/**
 * 發送AJAX並顯示加載提示
 * 當 AJAX 執行預估時間較長時使用此方法，會顯示加載提示框，並且可以取消請求
 * 
 * @param {string} url - 呼叫的 API
 * @param {Object} pdata - 需要傳遞給後端的資料
 * @param {function} callback - 當 AJAX 請求成功後執行的函數
 * @param {...Object} args - 傳遞給 callback 函數的額外參數
 * 
 * @example
 * showLoading(url, pdata, updateLoanRecordTable, sys_startyear, sys_endyear);
 */
function showLoading(url, pdata, callback, ...args) {
    let loadingShown = false;
    let ajaxDone = false;
    let ajaxRequest;
    let isAborted = false;

    let timer = setTimeout(() => {
        if (ajaxDone) return;
        loadingShown = true;
        Swal.fire({
            title: '處理中...',
            text: '請稍候',
            allowOutsideClick: false,
            showConfirmButton: true,
            confirmButtonText: '取消',
            customClass: {
                popup: 'custom-popup'
            },
            didOpen: () => {
                Swal.showLoading();
                const confirmButton = Swal.getConfirmButton();
                const footer = Swal.getFooter();
                if (footer) {
                    footer.appendChild(confirmButton); // 移動到 footer 區域
                }
                confirmButton.style.margin = '0 auto';
                confirmButton.style.display = 'block';
            }
        }).then((result) => {
            if (result.isConfirmed && ajaxRequest) {
                isAborted = true;
                ajaxRequest.abort();
            }
        });
    }, 200);

    // 執行 callback，並等待完成
    ajaxRequest = ajaxPostAsync(url, pdata, callback, ...args);

    ajaxRequest.always(() => {
        ajaxDone = true;
        clearTimeout(timer);
        if (loadingShown && Swal.isVisible()) {
            Swal.close();
        }
    }).fail((err) => {
        if (!isAborted) {
            //console.error("AJAX 發生錯誤:", err);
        }
    });
}

// 給長時讀取用
function ajaxPostAsync(url, pdata, callback, ...args) {
    // 發送 AJAX 請求
    return $.ajax({
        type: "post",
        url: url,
        data: pdata,
        dataType: "json",
        async: true,
        success: function (result) {
            if (result.rs !== undefined && result.rs == "1") {
                // 只有在 callback 是函式時才呼叫
                if (typeof callback === "function") {
                    callback(result, ...args);
                }
            } else {
                Swal.fire({
                    title: result.msg,
                    text: '失敗',
                    icon: 'error'
                });
            }
        },
        error: function (err) {
            if (err.statusText !== "abort") {
                Swal.fire({
                    title: '發生錯誤，請聯絡網站管理員。',
                    text: '失敗',
                    icon: 'error'
                });
            }
        }
    });
}

function initDataTable(tableId, tablePage = 0, callback) {
    let $table = $(tableId);

    // 檢查 DataTable 是否已初始化，若已初始化則清除並銷毀
    if ($.fn.DataTable.isDataTable($table)) {
        $table.DataTable().clear().destroy();
    }

    // 初始化 DataTable
    let dataTable = $table.DataTable({
        "iDisplayLength": 25,
        "aLengthMenu": [
            [25, 50, 100, -1],
            [25, 50, 100, "All"]
        ],
        "stateSave": true,
        "sDom": "<'row'<'col-md-6'li><'col-md-6 text-right'f>r>t<'row'<'col-md-12'i><'col-md-12 center-block'p>>",
        "sPaginationType": "full_numbers",
        "language": {
            "url": "js/Chinese-traditional.json"
        },
        "columnDefs": [{
            "targets": 'no-sort',
            "orderable": false,
        }],
        "initComplete": function (settings, json) {
            dataTable.page(tablePage).draw('page');
            if (typeof callback === "function") {
                callback();
            }
        }
    });

    return dataTable;
}

function redrawWithPageFix(table, pageInfoBefore) {
    table.draw(false);

    let pageInfoAfter = table.page.info();

    // 如果新增了一頁 → 跳最後一頁
    if (pageInfoAfter.pages > pageInfoBefore.pages) {
        table.page('last').draw(false);
    }
    // 如果當前頁超出範圍 → 回到最後一頁
    else if (pageInfoBefore.page >= pageInfoAfter.pages && pageInfoAfter.pages > 0) {
        table.page(pageInfoAfter.pages - 1).draw(false);
    }
}
