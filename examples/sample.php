<?php

require_once dirname( __FILE__ ) . '/../vendor/autoload.php';

// Boilerplate to make an api call
try {

    define( 'API_KEY', ( getenv( 'API_KEY' ) ? getenv( 'API_KEY' ) : null ) );

    $objClient = new ListenNotes\PodcastApi\Client( API_KEY );
    $strResponse = $objClient->typeahead( [ 'q' => 'pod', 'show_podcasts' => '1' ] );
    $arrHeaders = $objClient->getHeaders();

    print("\n=== Some account info ===\n");
    printf( "Free Quota this month: %s requests\n" , $arrHeaders['x-listenapi-freequota'] );
    printf( "Usage this month: %s requests\n" , $arrHeaders["x-listenapi-usage"] );
    printf( "Next billing date: %s\n" , $arrHeaders["x-listenapi-nextbillingdate"] );

    print("\n=== Response data ===\n");
    print_r( json_decode( $strResponse ) );

} catch ( ListenNotes\PodcastApi\Exception\APIConnectionException $objException ) {
    print("Failed ot connect to Listen API servers");
} catch ( ListenNotes\PodcastApi\Exception\AuthenticationException $objException ) {
    print("Wrong api key, or your account has been suspended!");
} catch ( ListenNotes\PodcastApi\Exception\InvalidRequestException $objException ) {
    print("Wrong parameters!");
} catch ( ListenNotes\PodcastApi\Exception\NotFoundException $objException ) {
    print("Endpoint not exist or the podcast / episode not exist!");
} catch ( ListenNotes\PodcastApi\Exception\RateLimitException $objException ) {
    print("You have reached your quota limit!");
} catch ( ListenNotes\PodcastApi\Exception\ListenApiException $objException ) {
    print("Something wrong on Listen Notes servers");
} catch ( Exception $e ) {
    print("Other errors that may not be related to Listen API");
}

// $strResponse = $objClient->search( [ 'q' => 'startup' ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchBestPodcasts()
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchPodcastById( [ 'id' => '4d3fe717742d4963a85562e9f84d8c79' ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchEpisodeById( [ 'id' => '6b6d65930c5a4f71b254465871fed370' ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->batchFetchEpisodes( [ 'ids' => 'c577d55b2b2b483c969fae3ceb58e362,0f34a9099579490993eec9e8c8cebb82' ]);
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->batchFetchPodcasts( [ 'ids' => '3302bc71139541baa46ecb27dbf6071a,68faf62be97149c280ebcc25178aa731,37589a3e121e40debe4cef3d9638932a,9cf19c590ff0484d97b18b329fed0c6a' ]);
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchCuratedPodcastsListById( [ 'id' => 'SDFKduyJ47r' ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchCuratedPodcastsLists( [ 'page' => 2 ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchPodcastGenres( [ 'top_level_only' => 0 ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchPodcastRegions()
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchPodcastLanguages()
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->justListen()
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchRecommendationsForPodcast( [ 'id' => '25212ac3c53240a880dd5032e547047b', 'safe_mode' => 1 ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchRecommendationsForEpisode( [ 'id' => '914a9deafa5340eeaa2859c77f275799', 'safe_mode' => 1 ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchPlaylistById( [ 'id' => 'm1pe7z60bsw', 'type' ='podcast_list' ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->fetchMyPlaylists()
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->submitPodcast( [ 'rss' => 'https://feeds.megaphone.fm/committed' ] );
// print_r( json_decode( $strResponse ) );

// $strResponse = $objClient->deletePodcast( [ 'id' => '4d3fe717742d4963a85562e9f84d8c79', 'reason' => 'the podcaster wants to delete it'] );
// print_r( json_decode( $strResponse ) );
