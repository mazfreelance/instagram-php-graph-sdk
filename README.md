# Instagram SDK for PHP

This repository contains the open source PHP SDK that allows you to access the Facebook Platform from your PHP app.

Disclamer: This SDK using Instagram Graph API and encourage to use "Instagram Basic Display API".

## Requirement
- PHP >=5.4.1
- Guzzle 5.x
- PHPunit 4.x
- mockery 0.8

## Installation
The preferred installation method is via composer. You can add the library as a dependency via:
```sh
$ composer require maztech/php-instagram-graph-sdk
```

## Usage

```php
<?php
require_once __DIR__ . '/vendor/autoload.php'; // change path as needed

$ig = new \Instagram\Instagram([
  'app_id' => '{app-id}',
  'app_secret' => '{app-secret}'
]);

// Use one of the helper classes to get a Instagram\Authentication\AccessToken entity.
$helper = $ig->getRedirectLoginHelper();

// instagram login - https://api.instagram.com/oauth/authorize?app_id={$clientId}&redirect_uri={$redirecUri}&scope=user_profile,user_media&response_type=code
$helper->getLoginUrl(); // get authorization url

// instagram callback
$helper->getAccessToken(); // get access token from code

try {
    $queryParams = http_build_query([
        'access_token' => '{access-token}'
    ]);
    $response = $ig->get('/me?{$queryParams}');
} catch(\Instagram\Exceptions\InstagramResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(\Instagram\Exceptions\InstagramSDKException $e) {
  // When validation fails or other local issues
  echo 'Instagram SDK returned an error: ' . $e->getMessage();
  exit;
}
```

This SDK was referred at Facebook Developer site [here](https://developers.facebook.com/docs/instagram-basic-display-api/guides)

## Copyright & License
PHP SDK for Instagram Graph API is Copyright (c) 2022 Mohd Azmin if not otherwise stated. The code is distributed under the terms of the [MIT License](https://github.com/mazfreelance/instagram-php-graph-sdk/blob/main/LICENSE).

