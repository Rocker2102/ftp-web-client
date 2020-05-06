<?php
    class ftp_operations {
        private $connect = false;

        function __construct($host, $port = 21) {
            $tmp = @ftp_connect($host, $port);
            if ($tmp) {
                $this->connect = $tmp;
            }
        }

        public function getConnectVar() {
            if ($this->connect) {
                return $this->connect;
            } else {
                return false;
            }
        }

        public function login($username = "", $password = "") {
            if ($this->connect && @ftp_login($this->connect, $username, $password)) {
                return true;
            } else {
                return false;
            }
        }

        public function ftpStatus() {
            if ($this->connect) {
                return true;
            } else {
                return false;
            }
        }

        public function rootDir() {
            if($this->connect && @ftp_cdup($this->connect)) {
                return true;
            } else {
                return false;
            }
        }

        public function chdir($dir = ".") {
            if (@ftp_chdir($this->connect, $dir)) {
                return true;
            } else {
                return false;
            }
        }

        public function getPwd() {
            if ($this->connect) {
                return ftp_pwd($this->connect);
            } else {
                return false;
            }
        }

        public function getRawList($dir = "") {
            return ftp_rawlist($this->connect, $dir);
        }

        public function getMlsd($dir = "") {
            return ftp_mlsd($this->connect, $dir);
        }

        public function getLastModifiedTime($file) {
            return @ftp_mdtm($this->connect, $file);
        }

        function __destruct() {
            if ($this->connect) {
                ftp_close($this->connect);
            }
        }
    }
?>