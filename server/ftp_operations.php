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

        public function login($username, $password) {
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

        function __destruct() {
            if ($this->connect) {
                ftp_close($this->connect);
            }
        }
    }
?>