<?php

include __DIR__ . '/../bootstrap.php';

$userInfo = getUser();

$content = file_get_contents(__DIR__ . '/../page-templates/secret-page.html');

if(empty($userInfo)) {
    header('HTTP/1.1 403 Forbidden');
    $content = file_get_contents(__DIR__ . '/../page-templates/403.html');
}

echo str_replace(
    array_keys($templateVariableMap),
    array_values($templateVariableMap),
    $content
);
