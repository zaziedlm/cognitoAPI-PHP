<?php

include __DIR__ . '/../bootstrap.php';

$content = file_get_contents(__DIR__ . '/../page-templates/forgotten-password.html');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['userName'];
    forgotPassword($username);
    header('Location: reset-password.php?username=' . $username);
    exit();
}

echo str_replace(
    array_keys($templateVariableMap),
    array_values($templateVariableMap),
    $content
);