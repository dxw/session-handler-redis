<?php

require_once __DIR__.'/common.php';

session_start();
if (isset($_SESSION[$_GET['key']])) {
	echo $_SESSION[$_GET['key']];
} else {
	echo "";
}
