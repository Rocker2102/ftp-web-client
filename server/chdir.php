<?php
    $send = new stdClass;
    $details = [];

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

        if ($username = getSessionVar("FTP_Username")) {
            if (!getSessionVar("FTP_Password")) {
                $password = "";
            } else {
                $password = getSessionVar("FTP_Password");
            }
    
            if ($ftp->login($username, $password)) {
                $send->status = "Connected to ".$ip.":".$port."@".$username;
            } else {
                $send->status = "Unable to connect with given username & password!";
                exitScript($send, 1, "Unable to connect to host at ".$ip.":".$port." with given credentials!");
            }
        } else if ($ftp->login()) {
            $send->status = "Connected to ".$ip.":".$port;
        } else if ($ftp->login("anonymous")) {
            $send->status = "Connected to ".$ip.":".$port."@anonymous";
        } else {
            $send->status = "Unable to connect to host at ".$ip.":".$port;
            exitScript($send, 1, "Anonymous users are not allowed!");
        }

        if (!$ftp->ftpStatus()) {
            exitScript($send, 1, "Unable to connect to host at ".$ip.":".$port);
        }

        $send->dirName = $chdir;

        if ($ftp->chdir($chdir)) {
            $dirDetailed = $ftp->getRawList();
            $dirParsed = $ftp->getMlsd();
            setSessionVar("FTP_Cd", $ftp->getPwd());
        } else if ($ftp->getLastModifiedTime($chdir)) {
            exitScript($send, 1, "Selected object cannot be opened!");
        } else {
            exitScript($send, 1, "Unable to change directory.!");
        }

        $send->list = formList($ftp->getPwd());
        setSessionVar("FTP_Cd", $ftp->getPwd());

        if (is_array($dirParsed) && count($dirParsed) > 1) {
            $send->dir = formArr($dirDetailed, $dirParsed, $ftp->getPwd());
            $send->pwd = getSessionVar("FTP_Cd");
            exitScript($send, 0, "");
        } else {
            $send->status = "Connected. No directories/files to list!";
            $send->dir = [];
            exitScript($send, 0, "No directories/files to list!");
        }
    }
?>