<?php

include __DIR__ . '/../bootstrap.php';

$content = file_get_contents(__DIR__ . '/../page-templates/reset-password.html');

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $userName = $_POST['username'];
    $password = $_POST['password'];
    $code = $_POST['code'];

    confirmForgotPassword($userName, $password, $code);
    header('Location: index.php?reset');
}

echo str_replace(
    array_keys($templateVariableMap),
    array_values($templateVariableMap),
    $content
);
