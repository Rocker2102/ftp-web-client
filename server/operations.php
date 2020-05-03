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

    function verifyData(array $data, array $requiredKeys = [], array $validKeys = []) {
        $newData = [];

        for ($i = 0; $i < count($requiredKeys); $i++) {
            if (isset($data[$requiredKeys[$i]]) && !empty($data[$requiredKeys[$i]])) {
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

    function formObj($arr) {
        return $arr;
    }

    function exitScript($jsonObj, $errorVal = 1, $info = "Script error!") {
        $jsonObj->error = $errorVal;
        $jsonObj->info = $info;
        echo json_encode($jsonObj);
        exit();
    }
?>
