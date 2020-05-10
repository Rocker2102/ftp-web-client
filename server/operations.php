<?php
    session_start();

    date_default_timezone_set("Asia/Kolkata");

    function getHash($data) {
        return hash("sha256", $data.HASH_SALT);
    }

    function getClientIP() {
        if ($_SERVER["REMOTE_ADDR"] == "::1") {
            return "localhost";
        } else {
            return $_SERVER["REMOTE_ADDR"];
        }
    }

    function setSessionVar($index, $data) {
       $_SESSION[$index] = $data;

       if ($_SESSION[$index] == $data) {
           return $_SESSION[$index];
       } else {
           return false;
       }
    }

    function getSessionVar($index) {
        if (isset($_SESSION[$index])) {
            return $_SESSION[$index];
        } else {
            return false;
        }
    }

    function unsetSessionVar($index) {
        $_SESSION[$index] = null;
        unset($_SESSION[$index]);
    }

    function removeEmptyAndReindex(array $data) {
        $i = 0;
        $newArr = [];
        foreach ($data as $key => $value) {
            if ($value == "0" || !empty($value) || strlen($value) != 0) {
                array_push($newArr, $value);
            }
        }
        return $newArr;
    }

    function verifyData(array $data, array $requiredKeys = [], array $validKeys = []) {
        $newData = [];

        for ($i = 0; $i < count($requiredKeys); $i++) {
            if (isset($data[$requiredKeys[$i]]) && (!empty($data[$requiredKeys[$i]]) || $data[$requiredKeys[$i]] == "0")) {
                $newData[$requiredKeys[$i]] = $data[$requiredKeys[$i]];
            } else {
                return false;
            }
        }

        for ($i = 0; $i < count($validKeys); $i++) {
            if (isset($data[$validKeys[$i]]) && !empty($data[$validKeys[$i]])) {
                $newData[$validKeys[$i]] = $data[$validKeys[$i]];
            }
        }
        
        return $newData;
    }

    function getRandomStr(int $len = 8, $mixMode = 6) {
        $numArr = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
        $charArr = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"];
        $specialArr = ["!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "_", "+", "=", "{", "}", "[", "]", ":", ";", "'", "\"", "<", ">", ".", "?", "/", "|", "\\", "~", "`"];
        $tmpArr = [];
        $randomStr = "";

        switch ($mixMode) {
            case 6: $tmpArr = array_merge($numArr, $charArr); break;
            case 7: $tmpArr = array_merge($numArr, $charArr, $specialArr); break;
            case 4: $tmpArr = $numArr; break;
            case 2: $tmpArr = $charArr; break;
            case 5: $tmpArr = array_merge($numArr, $specialArr); break;
            case 3: $tmpArr = array_merge($charArr, $specialArr); break;
            case 1: $tmpArr = $specialArr;
            default: $tmpArr = array_merge($numArr, $charArr, $specialArr); break;
        }

        shuffle($tmpArr);
        $arrMaxLen = count($tmpArr) - 1;

        for ($i = 0; $i < $len; $i++) {
            $randomStr .= $tmpArr[mt_rand(0, $arrMaxLen)];
        }

        return $randomStr;
    }

    function formArr(array $detailed, array $parsed, $pwd = "") {
        $arr = [];
        $tmp = "";

        $diffConstant = count($parsed) - count($detailed);

        if ($diffConstant < 0) {
            $begin = 2;
        } else {
            $begin = 0;
        }

        for ($i = $begin; $i < count($detailed); $i++) {
            $obj = new stdClass;
            $tmp = removeEmptyAndReindex(explode(" ", $detailed[$i]));
            $obj->chmod = $tmp[0];
            $obj->number = $tmp[1];
            $obj->user = $tmp[2];
            $obj->group = $tmp[3];
            $obj->name = $parsed[$i + $diffConstant]["name"];

            if ($pwd == "/" || $pwd == ".")  {
                $obj->chdir = $obj->name;
            } else {
                $obj->chdir = $pwd."/".$obj->name;
            }

            if (isset($parsed[$i + $diffConstant]["size"])) {
                $obj->size = $parsed[$i + $diffConstant]["size"];
            } else {
                $obj->size = "-";
            }

            if (isset($parsed[$i + $diffConstant]["type"])) {
                $obj->type = $parsed[$i + $diffConstant]["type"];
            } else {
                $obj->type = "-";
            }

            if (isset($parsed[$i + $diffConstant]["modify"])) {
                $obj->modified = formatDate($parsed[$i + $diffConstant]["modify"]);
            } else {
                $obj->modified = "-";
            }

            array_push($arr, $obj);
        }

        return $arr;
    }

    function getModDir($dir, int $stripEnd = 0) {
        $arr = explode("/", $dir);
        $arr = removeEmptyAndReindex($arr);
        $new_dir = "";
        
        for ($i = 0; $i < count($arr) - $stripEnd; $i++) {
            $new_dir .= "/".$arr[$i];
        }

        return $new_dir;
    }

    function getChdir($dir, $depth) {
        $arr = explode("/", $dir);
        $chdir = "";
        
        for ($i = 0; $i <= $depth; $i++) {
            $chdir .= $arr[$i]."/";
        }

        return $chdir;
    }

    function formList($dir) {
        $arr = explode("/", $dir);
        $arr[0] = "/ Root";
        $arr = removeEmptyAndReindex($arr);
        $newArr = [];

        for ($i = 0; $i < count($arr); $i++) {
            $obj = new stdClass;
            $obj->name = $arr[$i];
            $obj->chdir = getChdir($dir, $i);
            array_push($newArr, $obj);
        }

        return $newArr;
    }

    function formatDate($str) {
        $date = mb_substr($str, 6, 2)."-".mb_substr($str, 4, 2)."-".mb_substr($str, 0, 4);
        $time = mb_substr($str, 8, 2).":".mb_substr($str, 10, 2);
        return $date." ".$time;
    }

    function sessionLogin($ftp, stdClass &$send, $ip, $port) {
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
    }

    function updateDirList($ftp, stdClass &$send, $dir) {
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

    function exitScript(stdClass $jsonObj, $errorVal = 1, $info = "Script error!") {
        $jsonObj->error = $errorVal;
        $jsonObj->info = $info;
        echo json_encode($jsonObj);
        exit();
    }
?>
