<?php

include __DIR__ . '/../bootstrap.php';

$content = file_get_contents(__DIR__ . '/../page-templates/change-password.html');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['userName'];
    $newPassword = $_POST['newPassword'];
    $oldPassword = $_POST['oldPassword'];
    $result = changePasswordFirstTime($username, $oldPassword, $newPassword);
    //$token = $result->get('AuthenticationResult')['AccessToken'];
    //setAuthCookie($token);

    // この場所とは全く関係ないですが、API疎通のため、Eメール変更を呼び出し確認しました
    //$result = changeAttributeEmail('ka*****1', 'Ka*****2@', 's******@gmail.com');

    header('Location: index.php?password-changed');
    exit();
}

echo str_replace(
    array_keys($templateVariableMap),
    array_values($templateVariableMap),
    $content
);
