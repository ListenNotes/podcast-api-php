<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApiClient;

use PHPUnit\Framework\TestCase;

class PodcastApiClientTest extends TestCase
{
    /** @var PodcastApiClient */
    protected $podcastApiClient;

    protected function setUp(): void
    {
        $this->podcastApiClient = new PodcastApiClient();
    }

    public function testIsInstanceOfPodcastApiClient(): void
    {
        $actual = $this->podcastApiClient;
        $this->assertInstanceOf(PodcastApiClient::class, $actual);
    }

    public function testSetApiKey(): void
    {
        $objClient = $this->podcastApiClient;
        $this->assertSame( $objClient->getRequestHeader( 'X-ListenAPI-Key' ), null );

        $objClient = new PodcastApiClient( 'testKey' );
        $this->assertSame( $objClient->getRequestHeader( 'X-ListenAPI-Key' ), 'testKey' );
    }

    public function testSearchWithMock(): void
    {
        $objClient = $this->podcastApiClient;
        $strTerm = 'dummy';
        $strResponse = $objClient->search( $strTerm );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'results', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->results ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/search' );
        parse_str( $arrUrl['query'], $arrQuery );
        $this->assertSame( $arrQuery['q'], $strTerm );
    }
}
