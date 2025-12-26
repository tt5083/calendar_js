//另開新視窗 包含 focus
function openWindow(url) {
    'use strict';
    var win = null,
        winl = (screen.width - 400) / 2,
        wint = (screen.height - 300) / 2,
        settings;
    if (winl < 0) {
        winl = 0;
    }
    if (wint < 0) {
        wint = 0;
    }
    settings = 'height=300,';
    settings += 'width=400,';
    settings += 'top=' + wint + ','; //累加
    settings += 'left=' + winl + ','; //累加
    settings += 'scrollbars=yes'; //累加
    win = window.open(url, '', settings);
    win.window.focus();
}

//另開新視窗(沒有設置新視窗高度與寬度)
function openWindowNolength(url) {
    'use strict';
    var win = null,
        winl = (screen.width - 400) / 2,
        wint = (screen.height - 300) / 2,
        settings;
    if (winl < 0) {
        winl = 0;
    }
    if (wint < 0) {
        wint = 0;
    }

    settings = 'height=300,';
    settings += 'width=400,';
    settings += 'top=' + wint + ','; //累加
    settings += 'left=' + winl + ','; //累加
    settings += 'scrollbars=yes,'; //累加
    settings += 'resizable=yes,'; //是否可改變視窗大小
    settings += 'toolbar=yes,'; //是否顯示工具欄
    settings += 'location=yes,'; //是否要顯示工具網址列
    settings += 'menubar=yes'; //是否要顯示菜單
    win = window.open(url, '', settings);
}

//另開新視窗(fullscreen)
function openWindowFull(url) {
    'use strict'; //window.open("",'x','fullscreen=1,toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=auto,resizable=0');
    var popUpURL = url,
        splashWin = window.open("", 'x', 'fullscreen=1,toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=auto,resizable=0');
    splashWin.focus();
    splashWin.location = popUpURL;
}

//另開新視窗(fullscreen含工具欄)
function openWindowFullwithBar(url) {
    'use strict';
    var widthh = screen.width,
        heightt = screen.height,
        settings = 'width=' + widthh + ',height=' + heightt + ',top=0,left=0,scrollbars=yes';
    window.open(url, '', settings);
}

//
function MM_openBrWindow(theURL, winName, features) { //v2.0
    'use strict';
    var winl = (screen.width - 450) / 2,
        wint = (screen.height - 140) / 2;
    if (winl < 0) {
        winl = 0;
    }
    if (wint < 0) {
        wint = 0;
    }

    window.open(theURL, winName, 'top=' + wint + ',left=' + winl + ',' + features);
}

function OpenWindowvalue2(item1, item2, url) {
    'use strict';
    var winl = (screen.width - 400) / 2,
        wint = (screen.height - 300) / 2,
        w,
        settings;
    if (winl < 0) {
        winl = 0;
    }
    if (wint < 0) {
        wint = 0;
    }
    settings = 'height=480,';
    settings += 'width=640,';
    settings += 'top=' + wint + ','; //累加
    settings += 'left=' + winl + ','; //累加
    w = window.open(url + "?item1=" + item1 + "&item2=" + item2, "", settings);
}

function OpenWindowvalue4(item1, item2, item3, item4, url) {
    'use strict';
    var winl = (screen.width - 400) / 2,
        wint = (screen.height - 300) / 2,
        settings,
        items,
        w;
    if (winl < 0) {
        winl = 0;
    }
    if (wint < 0) {
        wint = 0;
    }
    settings = 'height=480,';
    settings += 'width=640,';
    settings += 'top=' + wint + ','; //累加
    settings += 'left=' + winl + ','; //累加
    settings += 'scrollbars=yes,';
    items = item1 + "|" + item2 + "|" + item3 + "|" + item4;
    w = window.open(url + "?items=" + items, "", settings);
}
    //取得網址參數
function getUrlVars() {
    'use strict';
    var vars = [],
        hash,
        hashes,
        i;
    hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    /*jslint plusplus: true */
    for (i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}
