<?php

include __DIR__ . '/../bootstrap.php';

$localTemplateVariableMap = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userName = $_POST['userName'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $result = signUp($userName, $email, $password);
        header('Location: confirmation.php?username=' . $userName);
        exit();
    } catch (InvalidArgumentException $e) {
        $localTemplateVariableMap = [
            '<!--__ERROR__-->' => nl2br($e->getMessage()),
        ];
    }
}

$templateVariableMap = array_merge(
    $localTemplateVariableMap,
    $templateVariableMap ?? []
);

$content = file_get_contents(__DIR__ . '/../page-templates/sign-up.html');

echo str_replace(
    array_keys($templateVariableMap),
    array_values($templateVariableMap),
    $content
);
