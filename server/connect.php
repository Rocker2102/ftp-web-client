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

    if (isset($data["ftp-username"]) && isset($data["ftp-password"])) {
        setSessionVar("FTP_Password", $data["ftp-password"]);
        $details["username"] = setSessionVar("FTP_Username", $data["ftp-username"]);
        $details["password"] = "*";

        if ($ftp->login($data["ftp-username"], $data["ftp-password"])) {
            $send->status = "Connected to ".$ip.":".$port."@".$data["ftp-username"];
        } else {
            $send->status = "Unable to connect with given username & password!";
            exitScript($send, 1, "Unable to connect to host at ".$ip.":".$port." with given credentials!");
        }
    } else if ($ftp->ftpStatus()) {
        $send->status = "Connected to ".$ip.":".$port."@anonymous";
    } else {
        exitScript($send, 1, "Unable to connect to host at ".$ip.":".$port);
    }

    $send->details = $details;
    $dirDetailed = ftp_rawlist($ftp->getConnectVar(), "");
    $dirParsed = ftp_mlsd($ftp->getConnectVar(), "");

    $send->detailed = $dirDetailed;
    $send->parsed = $dirParsed;

    if (is_array($dirParsed) && count($dirParsed) != 0) {
        $send->dir = formArr($dirDetailed, $dirParsed);
        setSessionVar("FTP_Status", true);
        setSessionVar("FTP_Host", $data["ftp-host"]);
        setSessionVar("FTP_Port", $data["ftp-port"]);
        exitScript($send, 0, "Connection established!");
    } else {
        exitScript($send, 1, "No directories found!");
    }
?>