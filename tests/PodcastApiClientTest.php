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

        $strKey = 'testKey';
        $objClient = new PodcastApiClient( $strKey );
        $this->assertSame( $objClient->getRequestHeader( 'X-ListenAPI-Key' ), $strKey );
    }

    public function testSearchWithMock(): void
    {
        $objClient = $this->podcastApiClient;
        $arrOptions = [ 'sort_by_date' => '1', 'q' => 'dummy' ];
        $strResponse = $objClient->search( $arrOptions );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'results', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->results ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/search' );
        parse_str( $arrUrl['query'], $arrQuery );
        $this->assertSame( $arrQuery['q'], $arrOptions['q'] );
        $this->assertSame( $arrQuery['sort_by_date'], $arrOptions['sort_by_date'] );
    }

    public function testSearchWithAuthenticationError(): void
    {
        $strKey = 'testKey';
        $objClient = new PodcastApiClient( $strKey );
        try {
            $objClient->search( 'dummy' );
            $this->fail( 'Did not throw an exception.' );
        } catch ( Exception\AuthenticationException $objException ) {
            $this->assertSame( $objClient->getStatusCode(), Exception\AuthenticationException::STATUS );
        } catch ( \Exception $objException ) {
            $this->fail( 'Wrong type of exception thrown.' );
        }
    }

    public function testTypeahead(): void
    {
        $objClient = $this->podcastApiClient;
        $strTerm = 'dummy';
        $arrOptions = [ 'show_podcasts' => '1' ];
        $strResponse = $objClient->typeahead( $strTerm, $arrOptions );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'terms', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->terms ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/typeahead' );
        parse_str( $arrUrl['query'], $arrQuery );
        $this->assertSame( $arrQuery['q'], $strTerm );
        $this->assertSame( $arrQuery['show_podcasts'], $arrOptions['show_podcasts'] );
    }

    public function testBestPodcasts(): void
    {
        $objClient = $this->podcastApiClient;
        $arrOptions = [ 'genre_id' => '23' ];
        $strResponse = $objClient->best_podcasts( $arrOptions );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'total', $objResponse );
        $this->assertGreaterThan( 0, $objResponse->total );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/best_podcasts' );
        parse_str( $arrUrl['query'], $arrQuery );
        $this->assertSame( $arrQuery['genre_id'], $arrOptions['genre_id'] );
    }

    public function testPodcastsById(): void
    {
        $objClient = $this->podcastApiClient;
        $strId = 'shkjhd';
        $strResponse = $objClient->podcasts( $strId );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'episodes', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->episodes ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/podcasts/' . $strId );
    }

    public function testEpisodesById(): void
    {
        $objClient = $this->podcastApiClient;
        $strId = 'shkjhd';
        $strResponse = $objClient->episodes( $strId );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'podcast', $objResponse );
        $this->assertGreaterThan( 0, strlen( $objResponse->podcast->rss ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/episodes/' . $strId );
    }

    public function testPodcasts(): void
    {
        $objClient = $this->podcastApiClient;
        $arrId = explode( ',', '996,777,888,1000' );
        $strResponse = $objClient->podcasts( $arrId );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'podcasts', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->podcasts ) );
        $this->assertSame( $objClient->getMethod(), 'POST' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/podcasts' );
    }

    public function testEpisodes(): void
    {
        $objClient = $this->podcastApiClient;
        $arrId = explode( ',', '996,777,888,1000' );
        $strResponse = $objClient->episodes( $arrId );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'episodes', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->episodes ) );
        $this->assertSame( $objClient->getMethod(), 'POST' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/episodes' );
    }

    public function testCuratedPodcasts(): void
    {
        $objClient = $this->podcastApiClient;
        $strId = 'shkjhd';
        $strResponse = $objClient->curated_podcasts( $strId );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'podcasts', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->podcasts ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/curated_podcasts/' . $strId );
    }

    public function testGenres(): void
    {
        $objClient = $this->podcastApiClient;
        $arrOptions = [ 'top_level_only' => '1' ];
        $strResponse = $objClient->genres( $arrOptions );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'genres', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->genres ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/genres' );
        parse_str( $arrUrl['query'], $arrQuery );
        $this->assertSame( $arrQuery['top_level_only'], $arrOptions['top_level_only'] );
    }

    public function testRegions(): void
    {
        $objClient = $this->podcastApiClient;
        $arrOptions = [ ];
        $strResponse = $objClient->regions( $arrOptions );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'regions', $objResponse );
        $this->assertGreaterThan( 0, count( (array) $objResponse->regions ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/regions' );
    }

    public function testLanguages(): void
    {
        $objClient = $this->podcastApiClient;
        $arrOptions = [ ];
        $strResponse = $objClient->languages( $arrOptions );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'languages', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->languages ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/languages' );
    }

    public function testJustListen(): void
    {
        $objClient = $this->podcastApiClient;
        $arrOptions = [ ];
        $strResponse = $objClient->just_listen( $arrOptions );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'audio_length_sec', $objResponse );
        $this->assertGreaterThan( 0, $objResponse->audio_length_sec );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/just_listen' );
    }

    public function testPodcastsByIdRecommendations(): void
    {
        $objClient = $this->podcastApiClient;
        $strId = 'shkjhd';
        $strResponse = $objClient->podcasts( $strId, [], true );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'recommendations', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->recommendations ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/podcasts/' . $strId . '/recommendations' );
    }

    public function testEpisodesByIdRecommendations(): void
    {
        $objClient = $this->podcastApiClient;
        $strId = 'shkjhd';
        $strResponse = $objClient->episodes( $strId, [], true );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'recommendations', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->recommendations ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/episodes/' . $strId . '/recommendations' );
    }

    public function testPlaylistsById(): void
    {
        $objClient = $this->podcastApiClient;
        $strId = 'shkjhd';
        $strResponse = $objClient->playlists( $strId, [] );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'items', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->items ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/playlists/' . $strId );
    }

    public function testPlaylists(): void
    {
        $objClient = $this->podcastApiClient;
        $strId = 'shkjhd';
        $arrOptions = [ 'page' => '2' ];
        $strResponse = $objClient->playlists( $strId, [] );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'items', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->items ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/playlists/' . $strId );
    }
/**


    def test_fetch_my_playlists_with_mock(self):
        client = podcast_api.Client()
        page = 2
        response = client.fetch_my_playlists(page=page)
        assert len(response.json().get("playlists", [])) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        assert url.path == "/api/v2/playlists"

    def test_submit_podcast_with_mock(self):
        client = podcast_api.Client()
        rss = "http://myrss.com/rss"
        response = client.submit_podcast(rss=rss)
        assert parse_qs(response.request.body)["rss"][0] == rss
        assert len(response.json().get("status", "")) > 0
        assert response.request.method == "POST"
        url = urlparse(response.url)
        assert url.path == "/api/v2/podcasts/submit"

    def test_delete_podcast_with_mock(self):
        client = podcast_api.Client()
        podcast_id = "asdfasdfdf"
        response = client.delete_podcast(id=podcast_id)
        assert len(response.json().get("status", "")) > 0
        assert response.request.method == "DELETE"
        url = urlparse(response.url)
        assert url.path == "/api/v2/podcasts/%s" % podcast_id
*/
}
