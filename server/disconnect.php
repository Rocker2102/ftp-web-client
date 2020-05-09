<?php
    $send = new stdClass;
    require "operations.php";

    if (getSessionVar("FTP_Status")) {
        unsetSessionVar("FTP_Status");
        unsetSessionVar("FTP_Host");
        unsetSessionVar("FTP_Port");
        unsetSessionVar("FTP_Username");
        unsetSessionVar("FTP_Password");
        unsetSessionVar("FTP_Cd");
        $send->status = 1;
        exitScript($send, 0, "Disconnected!");
    } else {
        $send->status = 0;
        exitScript($send, 0, "No existing session found!");
    }
?>