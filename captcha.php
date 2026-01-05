<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once("./phptextClass.php");
$phptextObj = new phptextClass();
$phptextObj->phpcaptcha('#bb141b','#ffffff',120,40,0,0);