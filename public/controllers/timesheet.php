<?php
/**
 * Created by PhpStorm.
 * User: Marat
 * Date: 14.11.2018
 * Time: 21:14
 */


include $_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php";

$timeSheetController = new \App\Controllers\EmployeesTimeSheetController();
$timeSheetController->execute($_POST);