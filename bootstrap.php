<?php

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Encryption\Encrypter;

include __DIR__ . '/srp.php';

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

define('AUTH_COOKIE_NAME', 'COGNITO_PHP_APP_COOKIE_NAME');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY'));

/**
 * @return CognitoIdentityProviderClient
 */
function getClient()
{
    static $client = null;

    if (is_null($client)) {
        $client = new CognitoIdentityProviderClient([
            'profile' => 'default',
            'region' => getenv('REGION'),
            'version' => '2016-04-18'
        ]);
    }

    return $client;
}

/**
 * Sign In / Login
 *
 * @param $username
 * @param $password
 * @return \Aws\Result
 */
function auth($username, $password)
{
    return getClient()->adminInitiateAuth([
        'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
        //'challengeName' => 'SRP_A',
        //'AuthFlow' => 'CUSTOM_AUTH',
        'ClientId' => getenv('CLIENT_ID'),
        'UserPoolId' => getenv('USERPOOL_ID'),
        'AuthParameters' => [
            'USERNAME' => $username,
            'PASSWORD' => $password,

        ],
    ]);
}

/**
 * Sign In / Login(CUSTOM_AUTH Flow)
 *
 * @param $username
 * @param $password
 * @return \Aws\Result
 */
function auth_srp($username, $password)
{
    // $srp = new srp();
    // $A = $srp->generateA($srp->getRandomSeed());

    $srp = new srp();
    $server_vars = array();
    $client_vars = array();

    //1. generate s, v (clinent generated, stored by server)
    $client_vars["username"] = $username;
    $client_vars["password"] = $password;

    $s = $srp->getRandomSeed();
    $x = $srp->generateX($s, $client_vars["username"], $client_vars["password"]);
    $client_vars["x"] = $x;
    $server_vars["s"]  = $s;
    $server_vars["v"]  = $srp->generateV($x);

    //2.1 client generate a, A and send A, I (username) to server
    $client_vars["a"] = $srp->getRandomSeed();
    $client_vars["A"] = $srp->generateA($client_vars["a"]);

    $result = getClient()->adminInitiateAuth([
        //'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
        //'AuthFlow' => 'USER_SRP_AUTH',
        'AuthFlow' => 'CUSTOM_AUTH',
        //'AuthFlow' => 'USER_PASSWORD_AUTH',
        //'challengeName' => 'SRP_A',
        //'CHALLENGE_NAME' => 'SRP_A',
        'ClientId' => getenv('CLIENT_ID'),
        'UserPoolId' => getenv('USERPOOL_ID'),
        'AuthParameters' => [
            'USERNAME' => $username,
            //'PASSWORD' => $password,
            //'challengeName' => 'SRP_A',
            'CHALLENGE_NAME' => 'SRP_A',
            'SRP_A' => $client_vars["A"]
        ],
    ]);
    return $result;
}



/**
 * When we add a user via AWS console, the user must update the password
 *
 * @param $userName
 * @param $oldPassword
 * @param $newPassword
 * @return \Aws\Result
 */
function changePasswordFirstTime($userName, $oldPassword, $newPassword)
{
    $result = getClient()->adminInitiateAuth([
        'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
        'ClientId' => getenv('CLIENT_ID'),
        'UserPoolId' => getenv('USERPOOL_ID'),
        'AuthParameters' => [
            'USERNAME' => $userName,
            'PASSWORD' => $oldPassword,
        ],
    ]);

    return getClient()->respondToAuthChallenge([
        'ChallengeName' => 'NEW_PASSWORD_REQUIRED',
        'ClientId' => getenv('CLIENT_ID'),
        'ChallengeResponses' => [
            'USERNAME' => $userName,
            'NEW_PASSWORD' => $newPassword,
        ],
        'Session' => $result->get('Session'),
    ]);
}

/**
 * We store the token in the browser cookies
 *
 * @param $accessToken
 * @return void
 */
function setAuthCookie($accessToken)
{
    $encrypter = new Encrypter(ENCRYPTION_KEY, 'AES-128-CBC');
    $encryptedToken = $encrypter->encrypt($accessToken);

    setcookie(AUTH_COOKIE_NAME, $encryptedToken, time() + 3600);
}

/**
 * @return mixed|string
 */
function getAuthCookie()
{
    $cookieVal = $_COOKIE[AUTH_COOKIE_NAME] ?? '';

    if (empty($cookieVal)) {
        return '';
    }

    $encrypter = new Encrypter(ENCRYPTION_KEY, 'AES-128-CBC');
    return $encrypter->decrypt($cookieVal);
}

function getUser()
{
    $authCookie = getAuthCookie();

    if (empty($authCookie)) {
        return null;
    }

    $result = getClient()->getUser([
        'AccessToken' => getAuthCookie()
    ]);

    return [
        'userName' => $result->get('Username'),
        'UserAttributes' => array_reduce($result->get('UserAttributes'), function ($carry, $item) {

            if (!in_array($item['Name'], ['name', 'email'])) {
                return $carry;
            }

            $carry[$item['Name']] = $item['Value'];
            return $carry;
        }, [])
    ];
}

/**
 * We send a validation code to email address of a specific user.
 *
 * @param $username
 * @return void
 */
function forgotPassword($username)
{
    getClient()->forgotPassword([
        'ClientId' => getenv('CLIENT_ID'),
        'Username' => $username
    ]);
}

/**
 * We change the password using the code that was sent.
 *
 * @param $username
 * @param $password
 * @param $code
 * @return void
 */
function confirmForgotPassword($username, $password, $code)
{
    getClient()->confirmForgotPassword([
        'ClientId' => getenv('CLIENT_ID'),
        'ConfirmationCode' => $code,
        'Password' => $password,
        'Username' => $username
    ]);
}

/**
 * We clear the cookie.
 *
 * @return void
 */
function logout()
{
    if (isset($_COOKIE[AUTH_COOKIE_NAME])) {
        unset($_COOKIE[AUTH_COOKIE_NAME]);
        setcookie(AUTH_COOKIE_NAME, '', time() - 3600);
    }
}

function cognitoSecretHash($userName)
{
    $message = $userName . getenv('CLIENT_ID');

    $hash = hash_hmac(
        'sha256',
        $message,
        getenv('CLIENT_SECRET'),
        true
    );

    return base64_encode($hash);
}

/**
 * We create a user. The user must be confirmed.
 *
 * @param $username
 * @param $email
 * @param $password
 * @return \Aws\Result
 */
function signUp($username, $email, $password)
{
    return getClient()->signUp([
        'ClientId' => getenv('CLIENT_ID'),
        'Username' => $username,
        'Password' => $password,
        'UserAttributes' => [
            [
                'Name' => 'name',
                'Value' => $username
            ],
            [
                'Name' => 'email',
                'Value' => $email
            ]
        ],
    ]);
}

/**
 * We confirm the user
 * @param $username
 * @param $code
 * @return \Aws\Result
 */
function confirmSignUp($username, $code)
{
    return getClient()->confirmSignUp([
        'ClientId' => getenv('CLIENT_ID'),
        'Username' => $username,
        'ConfirmationCode' => $code,
    ]);
}

/**
 * We get some information about currently logged in user
 */
$userInfo = getUser();

/**
 * This array is used to replace some text inside template files.
 */
$templateVariableMap = [
    '__HOME_URL__' => 'index.php',
    '__SIGN_IN__' => 'sign-in.php',
    '__SIGN_UP__' => 'sign-up.php',
    '__LOG_OUT__' => 'logout.php',
    '__RESET_PASSWORD__' => 'reset-password.php',
    '__CHANGE_PASSWORD__' => 'change-password.php',
    '__FORGOTTEN_PASSWORD__' => 'forgotten-password.php',
    '__SIGN_UP_CONFIRMATION__' => 'confirmation.php',
    '__SECRET_URL__' => 'secret-page.php',
    '// USER_INFO' => 'var user = ' . json_encode($userInfo) . ';' . PHP_EOL,
];
