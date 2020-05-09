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

    if ($op == "rename") {
        $requiredKeys = ["new-name", "dir"];
        $data = verifyData($_POST, $requiredKeys);

        if (!$data) {
            exitScript($send, 1, "Bad Request!");
        } else {
            $name = $data["new-name"];
            $dir = $data["dir"];
            
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

            $parentDir = getModDir($dir, 1);

            if ($ftp->rootDir() && $ftp->rename($dir, $parentDir."/".$name)) {    
                if ($ftp->chdir($parentDir)) {
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
                exitScript($send, 0, "Renamed!");
            } else {
                exitScript($send, 1, "Unable to rename specified file!");
            }
        }
    } else if ($op == "download") {

    } else if ($op == "delete") {

    } else {
        exitScript($send, 1, "Bad Request!");
    }
?>