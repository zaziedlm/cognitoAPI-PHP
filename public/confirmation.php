<?php

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

include __DIR__ . '/../bootstrap.php';

$localTemplateVariableMap = [
    //
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $result = confirmSignUp($_POST['userName'], $_POST['code']);
        header('Location: sign-in.php');
        exit();
    } catch (CognitoIdentityProviderException $e) {
        $localTemplateVariableMap = [
            '<!--__ERROR__-->' => $e->getAwsErrorMessage(),
        ];
    }
}

$content = file_get_contents(__DIR__ . '/../page-templates/sign-up-confirmation.html');

$templateVariableMap = array_merge(
    $localTemplateVariableMap,
    $templateVariableMap ?? []
);

echo str_replace(
    array_keys($templateVariableMap),
    array_values($templateVariableMap),
    $content
);
