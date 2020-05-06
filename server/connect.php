<?php
    $send = new stdClass;
    $details = [];

    require "operations.php";

    $requiredKeys = ["ftp-host", "ftp-port"];
    $validKeys = ["ftp-username", "ftp-password"];

    $data = verifyData($_POST, $requiredKeys, $validKeys);

    if (!$data) {
        exitScript($send, 1, "Bad Request!");
    }

    $ip = gethostbyname($data["ftp-host"]);

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        exitScript($send, 1, "Invalid Host Name!");
    }

    $port = $data["ftp-port"];
    if ($port < 1 || $port > 65535) {
        exitScript($send, 1, "Invalid Port Number!");
    }

    $details["host"] = $ip;
    $details["port"] = $port;

    require "ftp_operations.php";

    $ftp = new ftp_operations($ip, $port);

    if (isset($data["ftp-username"])) {
        if (!isset($data["ftp-password"])) {
            $data["ftp-password"] = "";
        }

        if ($ftp->login($data["ftp-username"], $data["ftp-password"])) {
            $send->status = "Connected to ".$ip.":".$port."@".$data["ftp-username"];
            $details["username"] = setSessionVar("FTP_Username", $data["ftp-username"]);
            setSessionVar("FTP_Password", $data["ftp-password"]);
        } else {
            $send->status = "Unable to connect with given username & password!";
            exitScript($send, 1, "Unable to connect to host at ".$ip.":".$port." with given credentials!");
        }
    } else if ($ftp->login()) {
        $send->status = "Connected to ".$ip.":".$port;
    } else if ($ftp->login("anonymous", "")) {
        $send->status = "Connected to ".$ip.":".$port."@anonymous";
        $details["username"] = setSessionVar("FTP_Username", "anonymous");
    } else {
        $send->status = "Unable to connect to host at ".$ip.":".$port;
        exitScript($send, 1, "Anonymous users are not allowed!");
    }

    if (!$ftp->ftpStatus()) {
        exitScript($send, 1, "Unable to connect to host at ".$ip.":".$port);
    }

    $send->details = $details;

    if ($ftp->rootDir()) {
        $dirDetailed = $ftp->getRawList();
        $dirParsed = $ftp->getMlsd();
    } else {
        exitScript($send, 1, "Unable to change directory to '/'");
    }

    $send->detailed = $dirDetailed;
    $send->parsed = $dirParsed;

    setSessionVar("FTP_Status", true);
    setSessionVar("FTP_Host", $ip);
    setSessionVar("FTP_Port", $port);

    if (is_array($dirParsed) && count($dirParsed) > 1) {
        $send->dir = formArr($dirDetailed, $dirParsed, $ftp->getPwd());
        setSessionVar("FTP_Cd", $ftp->getPwd());
        $send->pwd = getSessionVar("FTP_Cd");
        exitScript($send, 0, "Connection established!");
    } else {
        $send->status = "Connected. No directories to list!";
        $send->dir = [];
        exitScript($send, 0, "No directories to list!");
    }
?>