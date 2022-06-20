# AWS Cognito API try progress...

- Sign up
- Sign in
- Sign up confirmation
- Logout
- Forgotten password
- Reset password
- Protected page
- Encrypting access token with `illuminate/encryption`

## Installation

    mkdir cognitoAPI-PHP
    cd cognitoAPI-PHP
    git clone https://github.com/zaziedlm/cognitoAPI-PHP.git .
    composer install
    cp .env.example .env
    
    edit .env    ...your AWS configure, Cognito userpool config.

## Test server
    
    php -S 0.0.0.0:9900 -t public

http://localhost:9900/index.php

## caution!!

    AwsCognitoCustomSRP.php
    srp.php

    It's something I'm trying out SRP(Secure Remote Password)Auth.
