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
        $strTerm = 'dummy';
        $arrOptions = [ 'sort_by_date' => '1' ];
        $strResponse = $objClient->search( $strTerm, $arrOptions );
        $objResponse = json_decode( $strResponse );

        $this->assertObjectHasAttribute( 'results', $objResponse );
        $this->assertGreaterThan( 0, count( $objResponse->results ) );
        $this->assertSame( $objClient->getMethod(), 'GET' );
        $arrUrl = parse_url( $objClient->getUri() );
        $this->assertSame( $arrUrl['path'], '/api/v2/search' );
        parse_str( $arrUrl['query'], $arrQuery );
        $this->assertSame( $arrQuery['q'], $strTerm );
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

/**

    def test_fetch_curated_podcasts_list_by_id_with_mock(self):
        client = podcast_api.Client()
        curated_list_id = "asdfsdaf"
        response = client.fetch_curated_podcasts_list_by_id(id=curated_list_id)
        assert len(response.json().get("podcasts", [])) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        assert url.path == "/api/v2/curated_podcasts/%s" % curated_list_id

    def test_fetch_curated_podcasts_lists_with_mock(self):
        client = podcast_api.Client()
        page = 2
        response = client.fetch_curated_podcasts_lists(page=page)
        assert response.json().get("total") > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        params = parse_qs(url.query)
        assert params["page"][0] == str(page)
        assert url.path == "/api/v2/curated_podcasts"

    def test_fetch_podcast_genres_with_mock(self):
        client = podcast_api.Client()
        top_level_only = 1
        response = client.fetch_podcast_genres(top_level_only=top_level_only)
        assert len(response.json().get("genres", [])) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        params = parse_qs(url.query)
        assert params["top_level_only"][0] == str(top_level_only)
        assert url.path == "/api/v2/genres"

    def test_fetch_podcast_regions_with_mock(self):
        client = podcast_api.Client()
        response = client.fetch_podcast_regions()
        assert len(response.json().get("regions", {}).keys()) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        assert url.path == "/api/v2/regions"

    def test_fetch_podcast_languages_with_mock(self):
        client = podcast_api.Client()
        response = client.fetch_podcast_languages()
        assert len(response.json().get("languages", [])) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        assert url.path == "/api/v2/languages"

    def test_just_listen_with_mock(self):
        client = podcast_api.Client()
        response = client.just_listen()
        assert response.json().get("audio_length_sec", 0) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        assert url.path == "/api/v2/just_listen"

    def test_fetch_recommendations_for_podcast_with_mock(self):
        client = podcast_api.Client()
        podcast_id = "adfsddf"
        response = client.fetch_recommendations_for_podcast(id=podcast_id)
        assert len(response.json().get("recommendations", [])) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        assert url.path == "/api/v2/podcasts/%s/recommendations" % podcast_id

    def test_fetch_recommendations_for_episode_with_mock(self):
        client = podcast_api.Client()
        episode_id = "adfsddf"
        response = client.fetch_recommendations_for_episode(id=episode_id)
        assert len(response.json().get("recommendations", [])) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        assert url.path == "/api/v2/episodes/%s/recommendations" % episode_id

    def test_fetch_playlist_by_id_with_mock(self):
        client = podcast_api.Client()
        playlist_id = "adfsddf"
        response = client.fetch_playlist_by_id(id=playlist_id)
        assert len(response.json().get("items", [])) > 0
        assert response.request.method == "GET"
        url = urlparse(response.url)
        assert url.path == "/api/v2/playlists/%s" % playlist_id

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
