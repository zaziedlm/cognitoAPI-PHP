# AWS Cognito Application

- Sign up
- Sign in
- Sign up confirmation
- Logout
- Forgotten password
- Reset password
- Protected page
- Encrypting access token with `illuminate/encryption`

## Installation

    mkdir AWS-Cognito-PHP-Application
    cd AWS-Cognito-PHP-Application
    git clone git@github.com:mahmutbayri/AWS-Cognito-PHP-Application.git .
    composer install
    cp .env.example .env

## Test server
    
    php -S 0.0.0.0:9900 -t public

http://localhost:9900/index.php

## Screenshots

### Landing page

![](screenshots/landing-page.jpg)

### Sign in page

![](screenshots/sign-in-page.jpg)

### Sign up page

![](screenshots/sign-up-page.jpg)

### Password reset page

![](screenshots/password-reset-page.jpg)

### Password reset confirmation page

![](screenshots/password-reset-confirmation-page.jpg)

### Secret page

![](screenshots/secret-page.jpg)
