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
    git clone git@github.com:mahmutbayri/AWS-Cognito-PHP-Application.git .
    composer install
    cp .env.example .env
    
    edt .env    your AWS configure, Cognito userpool config.

## Test server
    
    php -S 0.0.0.0:9900 -t public

http://localhost:9900/index.php

