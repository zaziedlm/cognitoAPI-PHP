<?php

include __DIR__ . '/../bootstrap.php';

$content = file_get_contents(__DIR__ . '/../page-templates/index.html');

$localTemplateVariableMap = [
    //
];

$templateVariableMap = array_merge(
    $localTemplateVariableMap,
    $templateVariableMap ?? []
);

echo str_replace(
    array_keys($templateVariableMap),
    array_values($templateVariableMap),
    $content
);
