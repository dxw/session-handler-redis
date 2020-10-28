<?php
require_once __DIR__.'/common.php';

session_start();
echo $_SESSION[$_GET['key']];
