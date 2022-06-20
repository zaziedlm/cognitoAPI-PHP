<?php

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

include __DIR__ . '/../bootstrap.php';
include __DIR__ . '/../AwsCognitoCustomSRP.php';

$localTemplateVariableMap = [
    //
];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

// SRP auth tryCode...
    //     $client = new CognitoIdentityProviderClient([
    //         'profile' => 'default',
    //         'version' => '2016-04-18',
    //         'region' => 'ap-northeast-1',
    //         //'credentials' => false,
    //     ]);
        
    // //    $srp = new AwsCognitoCustomSRP($client, '3blgkg7hrfe1rgtl1p6mhqsq18', 'ap-northeast-1_qwkB5T1HH');
    //     $srp = new AwsCognitoCustomSRP($client, '53i1ep3ffoj2g3u169o3ubfv2g', 'ap-northeast-1_GvOCuMUzv');
        
    //     $result = $srp->authenticateUser($_POST['UserName'], $_POST['password']);
        
    //     if (! $result) {
    //         throw new \RuntimeException('Unable to obtain access token from AWS CognitoIdp.');
    //     }
        
    //     var_dump($result->toArray());
    //$result = auth_srp($_POST['UserName'], $_POST['password']);
        

        $result = auth($_POST['UserName'], $_POST['password']);
        
        // $chname = $result->get('ChallengeName');
        // $chparm = $result->get('ChallengeParameters')['SALT'];

        $token = $result->get('AuthenticationResult')['AccessToken'];
        setAuthCookie($token);

        if($result->get('ChallengeName') === 'NEW_PASSWORD_REQUIRED') {
            header('Location: change-password.php');
            exit();
        }


        header('Location: index.php');
        exit();
    } catch (CognitoIdentityProviderException $e) {
        $localTemplateVariableMap = [
            '<!--__ERROR__-->' => $e->getAwsErrorMessage(),
        ];
    }
}


$content = file_get_contents(__DIR__ . '/../page-templates/sign-in.html');

$templateVariableMap = array_merge(
    $localTemplateVariableMap,
    $templateVariableMap ?? []
);

echo str_replace(
    array_keys($templateVariableMap),
    array_values($templateVariableMap),
    $content
);
