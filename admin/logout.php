<?php
require_once '../core/App.php';
require_once '../core/Auth.php';

session_start();

$app = App::init();
$auth = new Auth($app->db());

// Выход из системы
$auth->logout();

// Перенаправляем на страницу входа
$app->redirect('login.php');