<?php
require_once __DIR__.'/common.php';

session_start();
$_SESSION[$_GET['key']] = $_GET['value'];
