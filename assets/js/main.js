$(document).ready(function() {
    $("select").formSelect();
    $(".modal").modal();
    $(".fixed-action-btn").floatingActionButton();
    $(".tooltipped").tooltip({exitDelay: 300});
    addOverlay();
    checkSession();
});

/* global variables */
let serverdata = 0;
let serverResponse = {time: 0, requests: 0};
let pwd = "";
let cached = {};
let currDir = "";

function showToast(htmlData, classData = "red white-text", icon = "info"){
    let toastIcon = "<i class='material-icons left'>" + icon + "</i>";
    return M.toast({html: toastIcon + htmlData, classes: classData});
}

function modDiv(id, html, classAdd = "", icon = "", classRemove = "") {
    $("#" + id).removeClass(classRemove).addClass(classAdd);
    if (icon == "") {
        return $("#" + id).html(html);
    } else {
        return $("#" + id).html("<i class='material-icons left'>" + icon + "</i>" + html);
    }
}

function customElement(tag, id = "", classList = "", attributeList = [], html = "", childList = "") {
    let element = document.createElement(tag);
    $(element).addClass(classList).html(html);

    if (id != "" && id.length != 0) {
        $(element).attr("id", id);
    }

    for (i = 0; i < attributeList.length; i++) {
        $(element).attr(attributeList[i].name, attributeList[i].value);
    }
    for (i = 0; i < childList.length; i++) {
        $(element).append(childList[i]);
    }

    return element;
}

function modInputs(id, status) {
    let elements = $("#" + id).find("input, select");
    $(elements).attr("disabled", status);
}

function checkSession() {
    let submitBtn = "ftp-form-submit-btn";

    $.getJSON(
        "server/check_session.php",
        function(data) {
            removeOverlay();
            updateResponseTime(data.endTime, data.beginTime);

            if (data.error == 0) {
                $("#op-fab").parent().removeClass("scale-out").addClass("scale-in");
                $("#" + submitBtn).attr("disabled", true);
                $("#info-container").attr("connection-status", "1");
                $("#disconnect-btn, #location-container").removeClass("hide");
                modDiv("info-text", data.status, "success-alert", "", "loader danger-alert info-alert warning-alert");
                modDiv(submitBtn, "Connected", "green", "verified_user", "red green orange");
                modInputs("ftp-form", true);
                if (typeof(Storage) !== undefined) {
                    serverdata = 1;
                    sessionStorage.setItem("host", data.host);
                    sessionStorage.setItem("port", data.port);
                }
                modLocationContainer(data.list, data.pwd, data.dir);
            } else {
                $("#" + submitBtn).attr("disabled", false);
                modDiv(submitBtn, "Connect", "orange", "navigate_next", "red green");
                modDiv("info-text", "", "", "", "loader success-alert danger-alert info-alert warning-alert");
            }
        }
    )
}

function disconnectFtp() {
    serverResponse.time = 0;
    serverResponse.requests = 0;
    cached = {};
    $("#op-fab").parent().removeClass("scale-in").addClass("scale-out");
    $("#ftp-form-container").removeClass("hide");
    $("#collection-container-1, #collection-container-2, #collection-container-3").html("");
    $("#location-container").addClass("hide");
    $("#info-container").attr("connection-status", "0");
    $("#ftp-form-submit-btn").attr("disabled", false);
    modDiv("ftp-form-submit-btn", "Connect", "orange", "navigate_next", "red green");
    modDiv("info-text", "", "", "", "loader success-alert danger-alert info-alert warning-alert");
    modInputs("ftp-form", false);

    $.getJSON(
        "server/disconnect.php",
        function(data) {
            if (Number(data.status) == 1) {
                showToast("Disconnected!", "yellow black-text", "link_off")
            }
        }
    )
}

function getSubType(name, type) {
    let tmp = name.split(".");
    tmp = tmp[tmp.length - 1];
    tmp = tmp.toLowerCase();

    let obj = {
        subtype: null, icon: null
    };

    let img = ["jpg", "jpeg", "png", "bmp", "svg", "tiff", "raw", "psd", "ico"];
    let vid = ["mp4", "mkv", "mpg", "mpeg", "mpeg4", "flv", "mov", "webm", "avi", "wmv"];
    let doc = ["doc", "docx", "xls", "xlsx", "ppt", "pptx", "txt", "log", ""];
    let audio = ["mp3", "ogg", "wav", "aac", "wma"];
    let archive = ["zip", "zipx", "tar", "gz", "rar", "bz2", "bz", "wim", "xz", "7z", "iso", "img"];
    let code = ["c", "cs", "cpp", "h", "py", "jar", "jad", "java", "html", "php", "css", "js", "json", "go", "dart", "bat"];
    let system = ["dll", "sys", "crt", "swp", "out", "drv", "ink", "dat", "efi", "ini"];
    let systemFolder = ["$", "."];
    let hidden = [".", "$", "@", "_"];
    let image = false;

    if (type == "dir") {
        obj.subtype = "dir";
        if (systemFolder.includes(name.substr(0, 1))) {
            obj.icon = "<i class='material-icons circle'>settings</i>";
        } else {
            obj.icon = "<i class='material-icons circle'>folder_open</i>";
        }
        return obj;
    }

    if (hidden.includes(name.substr(0, 1))) {
        obj.subtype = "hidden";
        obj.icon =  "visibility_off";
    } else if (tmp == "pdf") {
        obj.subtype = "doc";
        obj.icon = "picture_as_pdf";
    } else if (tmp == "vcf" || tmp == "vcard") {
        obj.subtype = "doc";
        obj.icon = "contacts";
    } else if (tmp == "apk" || tmp == "exe") {
        obj.subtype = "sw";
        obj.icon = "android";
    } else if (tmp == "asc") {
        obj.subtype = "doc";
        obj.icon = "https"
    } else if (img.includes(tmp)) {
        obj.subtype = "img";
        obj.icon = "landscape";
    } else if (vid.includes(tmp)) {
        obj.subtype = "vid";
        obj.icon = "movie";
    } else if (doc.includes(tmp)) {
        obj.subtype = "doc";
        obj.icon = "insert_drive_file";
    } else if (audio.includes(tmp)) {
        obj.subtype = "music";
        obj.icon = "music_note";
    } else if (archive.includes(tmp)) {
        obj.subtype = "archive";
        obj.icon = "archive";
    } else if (code.includes(tmp)) {
        obj.subtype = "code";
        obj.icon = "code";
    } else if (system.includes(tmp)) {
        obj.subtype = "system";
        image = true;
        obj.icon = "<img src='assets/img/icons/icon_systemFile.png' alt='icon' class='circle' />";
    } else {
        obj.subtype = "other";
        obj.icon = "help";
    }

    if (!image) {
        obj.icon = "<i class='material-icons circle'>" + obj.icon + "</i>";
    }
    
    return obj;
}

function formatSize(bytes) {
    if (isNaN(bytes)) {
        return "-";
    }
    if (bytes < 1000) {
        return bytes + " Bytes";
    } else if (bytes < 1000*1000) {
        return bytes/1000 + " KB";
    } else if (bytes < 1000*1000*1000) {
        return bytes/(1000*1000) + " MB";
    } else {
        return bytes/(1000*1000*1000) + " GB";
    }
}

function listDir(arr) {
    if (arr == undefined) {
        return;
    } else if (arr.length == 0) {
        showToast("No files/directories to list!", "black white-text", "info");
    }

    currDir = arr;
    cols = getCols();
    let fileTypeCount = {
        hidden: 0, system: 0, dir: 0, other: 0, archive: 0, code: 0, doc: 0, img: 0, music: 0, vid: 0, sw: 0
    }

    $("#collection-container-1, #collection-container-2, #collection-container-3").html("");
    let list1 = "<ul class='collection'>";
    let list2 = "<ul class='collection'>";
    let list3 = "<ul class='collection'>";
    let hide = getListingArr();
    let hiddenItems = 0;
    let totalItems = arr.length;

    for (i = 0; i < arr.length; i++) {
        let listItems = "";

        if (arr[i].chmod.substring(0, 1) == "d") {
            arr[i].type = "dir";
        } else {
            arr[i].type = "file";
        }

        let sub = getSubType(arr[i].name, arr[i].type);
        let attr = " data-dir='" + arr[i].name + "' data-type='" + arr[i].type + "' data-size='" + arr[i].size + "' data-chdir='" + arr[i].chdir +  "' data-subtype='" + sub.subtype + "' ";
        fileTypeCount[sub.subtype]++;

        if (hide.includes(sub.subtype)) {
            hiddenItems++;
            listItems += "<li class='collection-item avatar directory hide' " + attr + ">";
        } else {
            listItems += "<li class='collection-item avatar directory' " + attr + ">";
        }
            listItems += sub.icon;
            listItems += "<span class='title dir-name'>" + arr[i].name + "</span>";
            listItems += "<p>Permissions: <b>" + arr[i].chmod + "</b>, &nbsp; Group: <b>" + arr[i].group + "</b>, &nbsp; User: <b>" + arr[i].user + "</b></p>";
            if (arr[i].type == "file") {
                listItems += "<p>Size: <b>" + formatSize(arr[i].size) + "</b></p>";
            }
            if (arr[i].modified != undefined) {
                listItems += "<p>Last Modified: <b>" + arr[i].modified + "</b></p>";
            }

            listItems += "<a class='secondary-content icon-list' href='#!'>";
                listItems += "<i class='material-icons rename-icon' data-op='rename'>edit</i>";
                listItems += "<i class='material-icons download-icon' data-op='download'>get_app</i>";
                listItems += "<i class='material-icons delete-icon' data-op='delete'>delete</i>"
            listItems += "</a>";
        listItems += "</li>";

        if (cols != 2 && cols != 3) {
            list1 += listItems;
        } else if (cols == 2) {
            if ((i + 1) % 2 == 1) {
                list1 += listItems;
            } else {
                list2 += listItems;
            }
        } else if (cols == 3) {
            if ((i + 1) % 3 == 1) {
                list1 += listItems;
            } else if ((i + 1) % 3 == 2) {
                list2 += listItems;
            } else {
                list3 += listItems;
            }
        }
    }

    updateFileTypeCount(fileTypeCount);

    $("#item-count").html("Total Items: <b>" + totalItems + "</b>, Hidden Items: <b>" + hiddenItems + "</b>, Visible Items: <b>" + (totalItems - hiddenItems) + "</b>");
    $("#response-time").html("Average Server Response Time: <b>" + (serverResponse.time/serverResponse.requests).toFixed(2) + " seconds</b>");

    if (cols == 2) {
        list1 += "</ul>";
        list2 += "</ul>";
        $("#collection-container-3").addClass("hide");
        $("#collection-container-1, #collection-container-2").removeClass("s4 s12 hide").addClass("s6");
        $("#collection-container-1").append(list1);
        $("#collection-container-2").append(list2);
    } else if (cols == 3) {
        list1 += "</ul>";
        list2 += "</ul>";
        list3 += "</ul>";
        $("#collection-container-2, #collection-container-3").removeClass("hide");
        $("#collection-container-1, #collection-container-2, #collection-container-3").removeClass("s6 s12").addClass("s4");
        $("#collection-container-1").append(list1);
        $("#collection-container-2").append(list2);
        $("#collection-container-3").append(list3);
    } else {
        list1 += "</ul>";
        $("#collection-container-2, #collection-container-3").addClass("hide");
        $("#collection-container-1").removeClass("s4 s6").addClass("s12");
        $("#collection-container-1").append(list1);
    }
}

function modLocationContainer(list, cd, dir) {
    pwd = cd;
    listDir(dir);

    if (list == undefined) {
        return;
    } else if (list.length == 1) {
        $("#ftp-back-btn, #ftp-home-btn").attr("disabled", true);
    } else {
        $("#ftp-back-btn").attr({"disabled": false, "chdir": list[list.length - 2].chdir});
        $("#ftp-home-btn").attr("disabled", false);
    }

    $("#ftp-refresh-btn").attr("chdir", list[list.length - 1].chdir);

    let dirName = list[list.length - 1].chdir;
    let options = "";
    let extra = "";

    if (getCacheStatus() == "1") {
        dirName = dirName.replace(/\//g, "");
        cached[dirName] = {"dir": dir, "list": list, "cd": cd};
    }

    if (list != 0) {
        for (i = 0; i < list.length; i++) {
            if (i == list.length - 1 && list.length > 1) {
                extra = "selected disabled";
            } else {
                extra = "";
            }
            options += "<option value='" + list[i].chdir + "' " + extra + ">" + list[i].name + "</option>";
        }
    
        $("#location-container").find("select").html(options);
        $("select").formSelect();
    }
}

function changeDir(dir, refresh = 0) {
    let dirName = dir.replace(/\//g, "");

    if (getCacheStatus() == "1" && cached[dirName] != undefined && refresh == 0) {
        modLocationContainer(cached[dirName]["list"], cached[dirName]["cd"], cached[dirName]["dir"]);
        console.log("[CACHE] Loaded local copy");
        return;
    }

    $.ajax({
        url: "server/chdir.php",
        type: "POST",
        data: {chdir: dir},
        timeout: 90000,
        beforeSend: function() {
            addOverlay();
        },
        success: dirChangeSuccess,
        error: function() {
            removeOverlay();
            showToast("Unable to change directory!");
        }
    });
}

function dirChangeSuccess(receive) {
    removeOverlay();

    var data;
    try {
        data = JSON.parse(receive);
    } catch (e) {
        showToast("Data Error!");
        return;
    }

    updateResponseTime(data.endTime, data.beginTime)
    
    if (Number(data.error) == 0) {
        if (data.info != undefined && data.info != "") {
            showToast(data.info, "black white-text", "info");
        }

        modLocationContainer(data.list, data.pwd, data.dir);
    } else {
        showToast(data.info);
    }
}

function addOverlay() {
    $("#loader, #overlay").fadeIn();
}

function removeOverlay() {
    $("#loader, #overlay").fadeOut();
}

function fileMod(sendData, submitBtn = "") {
    let modalOp = ["rename", "new-folder", "new-file"];

    $.ajax({
        url: "server/files_op.php",
        data: sendData,
        type: "POST",
        timeout: 90000,
        beforeSend: function() {
            if (modalOp.includes(sendData["op"])) {
                $("#" + submitBtn).attr("disabled", true);
                modDiv(submitBtn, "Loading...", "disabled", "update");
            }
            addOverlay();
        },
        success: function(receive) {
            if (modalOp.includes(sendData["op"])) {
                $("#" + submitBtn).attr("disabled", false);
                modDiv(submitBtn, "Confirm", "", "done", "disabled");
            }

            removeOverlay();
            var data;

            try {
                data = JSON.parse(receive);
            } catch (e) {
                showToast("Data Error!", "red white-text");
                return;
            }
            $("#rename-modal, #new-folder-modal").modal("close");
            updateResponseTime(data.endTime, data.beginTime)

            if (data.status != undefined) {
                console.log(data.status);
            }

            if (data.error == 0) {
                showToast(data.info, "green white-text", "done_all");
                if (sendData["op"] == "download") {
                    window.open(data.link, "_blank");
                } else {
                    modLocationContainer(data.list, data.pwd, data.dir);
                }
            } else {
                showToast(data.info, "red white-text", "close");
            }
        },
        error: function() {
            if (sendData["op"] == "rename") {
                $("#" + submitBtn).attr("disabled", true);
                modDiv(submitBtn, "Rename", "", "done", "disabled");
            }

            removeOverlay();
        }
    });
}

function getCacheStatus() {
    return $("#ftp-cache-btn").attr("cache-status");
}

function getStatus(btn) {
    return $(btn).attr("data-status");
}

function getListingArr() {
    let arr = [];
    let el = $("#listing-options-modal").find(".listing-toggle[data-status='0']");
    for(i = 0; i < el.length; i++) {
        arr.push($(el[i]).attr("data-toggle"));
    }

    return arr;
}

function getCols() {
    let elements = $("input[name='view-type']");
    for(i = 0; i < elements.length; i++) {
        if ($(elements[i]).prop("checked")) {
            return Number($(elements[i]).val());
        }
    }
}

function updateResponseTime(end, begin) {
    let endTime = Number(end);
    let beginTime = Number(begin);
    
    serverResponse.time += (endTime - beginTime);
    serverResponse.requests++;
}

function updateFileTypeCount(obj) {
    for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
            $("#" + key + "-count").html("(" + obj[key] + ")");
        }
    }
}
