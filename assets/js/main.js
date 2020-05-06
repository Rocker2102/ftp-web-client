$(document).ready(function() {
    $('select').formSelect();
});

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

$("#ftp-form").on("submit", function(e) {
    e.preventDefault();

    let formdata = new FormData(this);
    let submitBtn = "ftp-form-submit-btn";
    let preloader = "<div class='progress'><div class='indeterminate'></div></div>";

    $.ajax({
        url: "server/connect.php",
        type: "POST",
        data: formdata,
        processData: false,
        contentType: false,
        timeout: 90000,
        beforeSend: function() {
            $("#" + submitBtn).attr("disabled", true);
            modDiv(submitBtn, "Connecting", "", "loop", "orange green red");
            modDiv("info-text", preloader, "loader", "", "success-alert danger-alert info-alert warning-alert");
        },
        success: function(receive) {
            var data;
            try {
                data = JSON.parse(receive);
            } catch (e) {
                $("#" + submitBtn).attr("disabled", false);
                modDiv(submitBtn, "Connect", "orange", "navigate_next", "red green orange");
                modDiv("info-text", "", "", "", "loader success-alert danger-alert info-alert warning-alert");
                showToast("Data Error!", "red white-text", "error_outline");
                return;
            }

            if (Number(data.error) == 0) {
                modInputs("ftp-form", true);
                $("#info-container").attr("connection-status", "1");
                $("#disconnect-btn").removeClass("hide");
                modDiv("info-text", data.status, "success-alert", "", "loader danger-alert info-alert warning-alert");
                modDiv(submitBtn, "Connected", "green", "verified_user", "red green orange");
                listDir(data.dir);
            } else {
                $("#" + submitBtn).attr("disabled", false);
                modDiv(submitBtn, "Connect", "orange", "navigate_next", "red green");
                modDiv("info-text", data.info, "danger-alert", "", "loader success-alert info-alert warning-alert");
                showToast("An error occurred!", "red white-text", "info");
                return;
            }
        },
        error: function() {
            showToast("Server error!");
            $("#" + submitBtn).attr("disabled", false);
            modDiv(submitBtn, "Connect", "orange", "navigate_next", "red green");
            modDiv("info-text", "", "", "", "loader success-alert danger-alert info-alert warning-alert");
            return;
        }
    });
});

$("#disconnect-btn").click(function() {
    $(this).addClass("hide");
    $("#info-container").attr("connection-status", "0");
    $("#ftp-form-submit-btn").attr("disabled", false);
    modDiv("ftp-form-submit-btn", "Connect", "orange", "navigate_next", "red green");
    modDiv("info-text", "", "", "", "loader success-alert danger-alert info-alert warning-alert");
    modInputs("ftp-form", false);
    $("#ftp-form-container").removeClass("hide");
    $("#collection-container").html("");
    disconnectFtp();
});

function disconnectFtp() {
    $.getJSON(
        "server/disconnect.php",
        function(data) {
            if (Number(data.status) == 1) {
                showToast("Disconnected!", "yellow black-text", "link_off")
            }
        }
    )
}

$("#info-container").click(function() {
    if ($(this).attr("connection-status") == "0") {
        return;
    } else {
        $("#ftp-form-container").toggleClass("hide");
    }
});

function getIcon(name) {
    let tmp = name.split(".");
    tmp = tmp[tmp.length - 1];
    tmp = tmp.toLowerCase();

    let img = ["jpg", "jpeg", "png", "bmp", "svg", "tiff", "raw", "psd", "ico"];
    let vid = ["mp4", "mkv", "mpg", "mpeg", "mpeg4", "flv", "mov", "webm", "avi", "wmv"];
    let doc = ["doc", "docx", "xls", "xlsx", "ppt", "pptx", "txt", "log", ""];
    let audio = ["mp3", "ogg", "wav", "aac", "wma"];
    let archive = ["zip", "zipx", "tar", "gz", "rar", "bz2", "bz", "wim", "xz", "7z", "iso", "img"];
    let code = ["c", "cs", "cpp", "h", "py", "jar", "jad", "java", "html", "php", "css", "js", "go", "dart"];
    let system = ["dll", "sys", "crt", "swp", "out", "drv", "ink", "dat", "efi", "ini"];

    let icon = "";
    let image = "";

    if (tmp == "pdf") {
        icon = "picture_as_pdf";
    } else if (tmp == "vcf" || tmp == "vcard") {
        icon = "contacts";
    } else if (tmp == "apk") {
        icon = "android";
    } else if (img.includes(tmp)) {
        icon = "landscape";
    } else if (vid.includes(tmp)) {
        icon = "movie";
    } else if (doc.includes(tmp)) {
        icon = "insert_drive_file";
    } else if (audio.includes(tmp)) {
        icon = "music_note";
    } else if (archive.includes(tmp)) {
        icon = "archive";
    } else if (code.includes(tmp)) {
        icon = "code";
    } else if (system.includes(tmp)) {
        img = "<img src='assets/img/icons/icon_systemFile.png' alt='icon' class='circle' />";
    } else {
        icon = "folder";
    }

    if (icon == "") {
        return img;
    } else {
        return "<i class='material-icons circle'>" + icon + "</i>";
    }
}

function formatSize(bytes) {
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
    $("#collection-container").html("");
    let ulContainer = "<ul class='collection'>";
    let listItems = "";

    for (i = 0; i < arr.length; i++) {
        let attr = " data-dir='" + arr[i].name + "' data-type='" + arr[i].type + "' data-size='" + arr[i].size + "' ";
        listItems += "<li class='collection-item avatar directory'>";
            listItems += getIcon(arr[i].name);
            listItems += "<span class='title dir-name' " + attr + ">" + arr[i].name + "</span>";
            listItems += "<p>Permissions: <b>" + arr[i].chmod + "</b>, &nbsp; Group: <b>" + arr[i].group + "</b>, &nbsp; User: <b>" + arr[i].user + "</b></p>";
            if (arr[i].type == "file") {
                listItems += "<p>Size: <b>" + formatSize(arr[i].size) + "</b></p>";
            }
            if (arr[i].modified != undefined) {
                listItems += "<p>Last Modified: <b>" + arr[i].modified + "</b></p>";
            }
            if (arr[i].type != "dir") {
                listItems += "<a class='secondary-content icon-list' href='#!'><i class='material-icons download-icon'>get_app</i><i class='material-icons delete-icon'>delete</i></a>";
            }
        listItems += "</li>";
    }

    ulContainer += listItems;
    ulContainer += "</ul>";
    $("#collection-container").append(ulContainer);
}

$("#collection-container").on("click", "ul > li > span.title", function() {
    let dir = $(this).attr("data-dir");
    let type = $(this).attr("data-type");

    if (type == "file") {
        showToast("Selected object is a file!", "yellow black-text");
        return;
    }
    
    $.ajax({
        url: "server/chdir.php",
        type: "POST",
        data: {dir: dir},
        timeout: 90000,
        beforeSend: function() {
            showToast("Requesting...");
        },
        success: function(receive) {
            var data;
            try {
                data = JSON.parse(receive);
            } catch (e) {
                showToast("Data Error!");
                return;
            }
            
            if (Number(data.error) == 0) {
                listDir(data.dir);
            } else {
                showToast(data.info);
            }
        },
        error: function() {
            showToast("Request Error!");
        }
    });
});
