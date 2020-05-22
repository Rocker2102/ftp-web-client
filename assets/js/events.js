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
                $("#op-fab").parent().removeClass("scale-out").addClass("scale-in");
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
    if (typeof(Storage) !== undefined) {
        serverdata = 0;
        sessionStorage.removeItem("host");
        sessionStorage.removeItem("port");
    }
    disconnectFtp();
});

$("#info-container").click(function() {
    if ($(this).attr("connection-status") == "0") {
        return;
    } else {
        $("#ftp-form-container").toggleClass("hide");
    }
});

$("#collection-container-1, #collection-container-2, #collection-container-3").on("click", "ul > li > span.title", function() {
    let dir = $(this).parent().attr("data-chdir");
    let type = $(this).parent().attr("data-type");

    if (type == "file") {
        let tmp = "<span>Files can't be opened!</span><a href='javascript:void(0)' onclick='changeDir(\"" + dir + "\")' class='btn-flat toast-action' style='color: #b71c1c'>Open anyway</a>"
        showToast(tmp, "grey darken-4");
        return;
    }
    
    changeDir(dir);
});

$("#location-container").find("select").on("change", function() {
    let dir = $(this).val();
    changeDir(dir);
});

$("#collection-container-1, #collection-container-2, #collection-container-3").on("click", "ul > li > a > i", function() {
    let operation = $(this).attr("data-op");
    let chdir = $(this).parent().parent().attr("data-chdir");
    let type = $(this).parent().parent().attr("data-type");
    let name = $(this).parent().parent().attr("data-dir");

    if (operation == "delete" && confirm("Are you sure?")) {
        data = {"op": "delete", "dir": chdir};
        fileMod(data);
    } else if (operation == "download") {
        if (type == "file") {
            data = {"op": "download", "dir": chdir};
            fileMod(data);   
        } else {
            showToast("Only files can be downloaded!", "black white-text", "info");
        }
    } else if (operation == "rename") {
        $("#rename-submit-btn").attr("disabled", true);
        $("#new-name").val(name);
        $("#new-name").attr({"old-val": name, "chdir": chdir});
        $("#rename-modal-heading").html("Rename '" + name + "'");
        M.updateTextFields();
        $("#rename-modal").modal("open");
    } else {
        return;
    }
});

$("#rename-form").on("submit", function(e) {
    e.preventDefault();
    let newName = $("#new-name").val();
    let chdir = $("#new-name").attr("chdir");
    data = {"op": "rename", "new-name": newName, "dir": chdir};
    $("#rename-submit-btn").attr("disabled", true);
    fileMod(data, "rename-submit-btn");
});

$("#new-name").on("input", function() {
    if ($(this).val().toLowerCase() == $(this).attr("old-val").toLowerCase() || $(this).val() == "") {
        $("#rename-submit-btn").attr("disabled", true);
    } else {
        $("#rename-submit-btn").removeClass("disabled").attr("disabled", false);
    }
});

$(".dir-op").click(function() {
    let op = $(this).attr("data-op");

    if (op == "upload-file") {
        showToast("Under Development", "yellow black-text", "gavel");
    } else if (op == "new-folder") {
        $("#new-folder-location").html("Location: " + pwd);
        $("#new-folder-modal").modal("open");
    } else if (op == "new-file") {
        showToast("Under Development", "yellow black-text", "gavel");
    } else {
        return;
    }
});

$("#new-folder").on("input", function() {
    let badChars = ["/", "\\", "\"", "<", ">", "?", "*", "|", ":"];
    let val = $(this).val();

    for (i = 0; i < badChars.length; i++) {
        if (val.includes(badChars[i])) {
            showToast("Folder/File name cannot contain '" + badChars[i] + "'", "yellow black-text");
            regEx = new RegExp("\\" + badChars[i], "g");
            val = val.replace(regEx, "");
        }
    }

    $("#new-folder-modal-heading").html("Create Folder: " + val);
    $(this).val(val);
});

$("#new-folder-form").on("submit", function(e) {
    e.preventDefault();
    let name = $("#new-folder").val();
    let dir = pwd;
    data = {"op": "new-folder", "name": name, "dir": dir};
    $("#new-folder-submit-btn").attr("disabled", true);
    fileMod(data, "new-folder-submit-btn");
});

$(".ftp-nav-btn").on("click", function() {
    let dir = $(this).attr("chdir");

    if ($(this).attr("id") == "ftp-refresh-btn") {
        changeDir(dir, 1);
    } else {
        changeDir(dir);
    }
});

$("#ftp-cache-btn").on("click", function() {
    if (getCacheStatus() == "1") {
        $(this).removeClass("green").addClass("red");
        $(this).attr({"cache-status": "0", "data-tooltip": "Caching disabled!"});
    } else {
        $(this).removeClass("red").addClass("green");
        $(this).attr({"cache-status": "1", "data-tooltip": "Caching enabled"});
    }
});

$(".listing-toggle").on("click", function() {
    if (getStatus($(this)) == "1") {
        $(this).removeClass("green").addClass("red");
        $(this).attr({"data-status": "0", "data-tooltip": "Disabled"});
    } else {
        $(this).removeClass("red").addClass("green");
        $(this).attr({"data-status": "1", "data-tooltip": "Enabled"});
    }
});

$("input[name='view-type']").on("change", function() {
    listDir(currDir);
});
