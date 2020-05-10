<?php
    $send = new stdClass;

    require "operations.php";

    if (!isset($_POST["op"]) || empty($_POST["op"])) {
        exitScript($send, 1, "Invalid Request!");
    } else if (!getSessionVar("FTP_Status")) {
        exitScript($send, 1, "Invalid Session!");
    }  else {
        $op = $_POST["op"];
    }

    $ip = getSessionVar("FTP_Host");
    $port = getSessionVar("FTP_Port");

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

    if ($op == "rename") {
        $requiredKeys = ["new-name", "dir"];
        $data = verifyData($_POST, $requiredKeys);

        if (!$data) {
            exitScript($send, 1, "Bad Request!");
        } else {
            $name = $data["new-name"];
            $dir = $data["dir"];
            $parentDir = getModDir($dir, 1);

            if ($ftp->rootDir() && $ftp->rename($dir, $parentDir."/".$name)) {    
                updateDirList($ftp, $send, $parentDir);

                $send->status = "File/Directory (".$dir.") was renamed successfully.";
                exitScript($send, 0, "Renamed!");
            } else {
                $send->status = "File/Directory (".$dir.") was not renamed!";
                exitScript($send, 1, "Unable to rename specified file/directory!");
            }
        }
    } else if ($op == "download") {

    } else if ($op == "delete") {
        $requiredKeys = ["dir"];
        $data = verifyData($_POST, $requiredKeys);

        if (!$data) {
            exitScript($send, 1, "Bad Request!");
        } else {
            $dir = $data["dir"];
            $parentDir = getModDir($dir, 1);

            if ($ftp->delete($dir)) {
                updateDirList($ftp, $send, $parentDir);

                $send->status = "File/Directory at ".$dir." deleted successfully.";
                exitScript($send, 0, "File Deleted!");
            } else {
                $send->status = "File/Directory at ".$dir." was not deleted! (If it is a directory, check if it is empty)";
                exitScript($send, 1, "Unable to delete file/directory!");
            }
        }
    } else {
        exitScript($send, 1, "Bad Request!");
    }

    function updateDirList($ftp, &$send, $dir) {
        if ($ftp->chdir($dir)) {
            $dirDetailed = $ftp->getRawList();
            $dirParsed = $ftp->getMlsd();   
            $send->list = formList($ftp->getPwd());
            $send->pwd = $ftp->getPwd();

            if (is_array($dirParsed) && count($dirParsed) > 1) {
                $send->dir = formArr($dirDetailed, $dirParsed, $ftp->getPwd());
            } else {
                $send->dir = [];
            }
        }
    }
?>