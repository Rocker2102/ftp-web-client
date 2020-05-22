<?php
    $send = new stdClass;
    $send->beginTime = microtime(true);

    require "operations.php";

    $requiredKeys = ["chdir"];
    $data = verifyData($_POST, $requiredKeys);

    if (!$data) {
        exitScript($send, 1, "Bad Request!");
    } else if (!getSessionVar("FTP_Status")) {
        exitScript($send, 1, "Invalid Session!");
    } else {
        $ip = getSessionVar("FTP_Host");
        $port = getSessionVar("FTP_Port");
        $chdir = $data["chdir"];

        require "ftp_operations.php";
        $ftp = new ftp_operations($ip, $port);

        sessionLogin($ftp, $send, $ip, $port);

        $send->dirName = $chdir;

        if ($ftp->chdir($chdir)) {
            $dirDetailed = $ftp->getRawList();
            $dirParsed = $ftp->getMlsd();
        } else if ($ftp->getLastModifiedTime($chdir)) {
            exitScript($send, 1, "File/Folder cannot be opened!");
        } else {
            exitScript($send, 1, "Unable to change directory.!");
        }

        $send->list = formList($ftp->getPwd());
        $send->pwd = setSessionVar("FTP_Cd", $ftp->getPwd());

        if (is_array($dirParsed) && count($dirParsed) > 1) {
            $send->dir = formArr($dirDetailed, $dirParsed, $ftp->getPwd());
            exitScript($send, 0, "");
        } else {
            $send->status = "Connected. No directories/files to list!";
            $send->dir = [];
            exitScript($send, 0, "No directories/files to list!");
        }
    }
?>