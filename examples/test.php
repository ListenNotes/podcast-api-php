<?php


require_once dirname( __FILE__ ) . '/../vendor/autoload.php';

try {

    define( 'API_KEY', ( getenv( 'API_KEY' ) ? getenv( 'API_KEY' ) : null ) );
    $objClient = new ListenNotes\PodcastApiClient\PodcastApiClient( API_KEY );
    // $strResult = $objClient->search( [ 'q' => 'startup' ] );
    $strResult = $objClient->batchFetchPodcasts( [ 'ids' => '111,222,333' ] );


    echo 'RESPONSE: ' . substr( $strResult, 0, 40 ) . '... ' . PHP_EOL;
    echo 'LENGTH: ' . strlen( $strResult ) . PHP_EOL;

    echo 'STATUS CODE: ' . $objClient->getStatusCode() . PHP_EOL;
    echo 'HEADERS: ' . print_r( $objClient->getHeaders(), true ) . PHP_EOL;
} catch ( Exception $e ) {
    echo 'EXCEPTION: ' . $e->getMessage();
    echo PHP_EOL;
}
