<?php
    $send = new stdClass;
    require "operations.php";

    if (!getSessionVar("FTP_Status")) {
        exitScript($send, 1, "No existing session found!");
    }

    $ip = getSessionVar("FTP_Host");
    $port = getSessionVar("FTP_Port");

    require "ftp_operations.php";
    $ftp = new ftp_operations($ip, $port);

    sessionLogin($ftp, $send, $ip, $port);

    $dir = getSessionVar("FTP_Cd");

    updateDirList($ftp, $send, $dir);
    $send->list = formList($ftp->getPwd());

    exitScript($send, 0, "Session restored!");
?>