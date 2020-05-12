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

    sessionLogin($ftp, $send, $ip, $port);

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

                $send->status = "[SUCCESS] File/Directory (".$dir.") renamed successfully.";
                exitScript($send, 0, "Renamed!");
            } else {
                $send->status = "[FAIL] File/Directory (".$dir.") not renamed!";
                exitScript($send, 1, "Unable to rename specified file/directory!");
            }
        }
    } else if ($op == "download") {
        $requiredKeys = ["dir"];
        $data = verifyData($_POST, $requiredKeys);

        if (!$data) {
            exitScript($send, 1, "Bad Request!");
        } else {
            $dir = $data["dir"];
            $username = getSessionVar("FTP_Username");
            $password = getSessionVar("FTP_Password");

            if ($username && $password) {
                $link = "ftp://".$username.":".$password."@".$ip.":".$port.getModDir($dir);
            } else if ($username) {
                $link = "ftp://".$username."@".$ip.":".$port.getModDir($dir);
            } else {
                $link = "ftp://".$ip.":".$port.getModDir($dir);
            }

            $send->link = $link;
            exitScript($send, 0, "Downloading...");
        }
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
                $send->status = "[SUCCESS] File/Directory at '".$dir."' deleted successfully.";
                exitScript($send, 0, "File/Directory Deleted!");
            } else {
                $send->status = "[FAIL] File/Directory at '".$dir."' was not deleted! (If it is a directory, check if it is empty)";
                exitScript($send, 1, "Unable to delete file/directory!");
            }
        }
    } else if ($op == "new-folder") {
        $requiredKeys = ["name", "dir"];
        $data = verifyData($_POST, $requiredKeys);
        $location = $data["dir"]."/".$data["name"];
        $badChars = ["/", "\\", "\"", "<", ">", "?", "*", "|", ":"];

        for ($i = 0; $i < count($badChars); $i++) {
            if (strpos($data["name"], $badChars[$i]) !== false) {
                exitScript($send, 1, "[ERROR] Invalid characters found in name!");
            }
        }

        if (!$data) {
            exitScript($send, 1, "Bad Request!");
        } else {
            if ($ftp->makeDir($location)) {
                updateDirList($ftp, $send, $data["dir"]);
                $send->status = "[SUCCESS] Directory Name: ".$data["name"].", Location: ".$data["dir"];
                exitScript($send, 0, "Directory '".$data["name"]."' successfully created");
            } else {
                $send->status = "[FAIL] Directory Name: ".$data["name"].", Location: ".$data["dir"];
                exitScript($send, 1, "Unable to create directory!");
            }
        }
    } else {
        exitScript($send, 1, "Bad Request!");
    }
?>