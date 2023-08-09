<?php

require_once __DIR__.'/common.php';

session_start();
unset($_SESSION[$_GET['key']]);
