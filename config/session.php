<?php
session_start();

$timeout = 1800; // 30 минут

if (isset($_SESSION['LAST_ACTIVITY']) && 
   (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {

    session_unset();
    session_destroy();
}

$_SESSION['LAST_ACTIVITY'] = time();