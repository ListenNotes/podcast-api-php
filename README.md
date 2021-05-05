# Podcast API PHP Library

[![Build Status](https://travis-ci.com/ListenNotes/podcast-api-php.svg?branch=main)](https://travis-ci.com/ListenNotes/podcast-api-php)

The Podcast API PHP library provides convenient access to the [Listen Notes Podcast API](https://www.listennotes.com/api/) from
applications written in the PHP language.

Simple and no-nonsense podcast search & directory API. Search the meta data of all podcasts and episodes by people, places, or topics. It's the same API that powers [the best podcast search engine Listen Notes](https://www.listennotes.com/).

If you have any questions, please contact [hello@listennotes.com](hello@listennotes.com?subject=Questions+about+the+PHP+SDK+of+Listen+API)

<a href="https://www.listennotes.com/api/"><img src="https://raw.githubusercontent.com/ListenNotes/ListenApiDemo/master/web/src/powered_by_listennotes.png" width="300" /></a>

## Installation

Install the official composer package of the Listen Notes Podcast API:

```sh
composer require listennotes/podcast-api
```


### Requirements

- PHP 7+

## Usage

The library needs to be configured with your account's API key which is
available in your [Listen API Dashboard](https://www.listennotes.com/api/dashboard/#apps). Set `API_KEY` to its
value:

```php

<?php

require_once dirname( __FILE__ ) . '/../vendor/autoload.php';

// Boilerplate to make an api call
try {

    define( 'API_KEY', ( getenv( 'API_KEY' ) ? getenv( 'API_KEY' ) : null ) );

    $objClient = new ListenNotes\PodcastApiClient\PodcastApiClient( API_KEY );
    $strResult = $objClient->typeahead( [ 'q' => 'startup', 'show_podcasts' => '1' ] );
    $arrHeaders = $objClient->getHeaders();

    print("\n=== Some account info ===\n");
    printf( "Free Quota this month: %s requests\n" , $arrHeaders['x-listenapi-freequota'] );
    printf( "Usage this month: %s requests\n" , $arrHeaders["x-listenapi-usage"] );
    printf( "Next billing date: %s\n" , $arrHeaders["x-listenapi-nextbillingdate"] );

} catch ( ListenNotes\PodcastApiClient\Exception\APIConnectionException $objException ) {
    print("Failed ot connect to Listen API servers");
} catch ( ListenNotes\PodcastApiClient\Exception\AuthenticationException $objException ) {
    print("Wrong api key, or your account has been suspended!");
} catch ( ListenNotes\PodcastApiClient\Exception\InvalidRequestException $objException ) {
    print("Wrong parameters!");
} catch ( ListenNotes\PodcastApiClient\Exception\NotFoundException $objException ) {
    print("Endpoint not exist or the podcast / episode not exist!");
} catch ( ListenNotes\PodcastApiClient\Exception\RateLimitException $objException ) {
    print("You have reached your quota limit!");
} catch ( ListenNotes\PodcastApiClient\Exception\ListenApiException $objException ) {
    print("Something wrong on Listen Notes servers");
} catch ( Exception $e ) {
    print("Other errors that may not be related to Listen API");
}

```

If `API_KEY` is null, then we'll connect to a [mock server](https://www.listennotes.com/api/tutorials/#faq0) that returns fake data for testing purposes.


### Handling exceptions

Unsuccessful requests raise exceptions. The class of the exception will reflect
the sort of error that occurred.

| Exception Class  | Description |
| ------------- | ------------- |
|  AuthenticationException | wrong api key or your account is suspended  |
| APIConnectionException  | fail to connect to API servers  |
| InvalidRequestException  | something wrong on your end (client side errors), e.g., missing required parameters  |
| RateLimitException  | you are using FREE plan and you exceed the quota limit  |
| NotFoundException  | endpoint not exist, or podcast / episode not exist  |
| ListenApiException  | something wrong on our end (unexpected server errors)  |

All exception classes can be found in [this folder](https://github.com/ListenNotes/podcast-api-php/blob/main/listennotes/Exception).

And you can see some sample code [here](https://github.com/ListenNotes/podcast-api-php/blob/main/examples/sample.php).


