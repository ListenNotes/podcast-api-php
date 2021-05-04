<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApiClient;

final class PodcastApiClient extends Client\Curl
{
    public function __construct( $strApiKey = '' )
    {
        parent::__construct( $strApiKey );
    }

    public function fetchMyPlaylists( $mixId = '', array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'playlists' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    public function justListen( array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'just_listen' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    public function fetchPodcastLanguages( array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'languages' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    public function fetchPodcastRegions( array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'regions' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    public function fetchPodcastsGenres( array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'genres' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    public function fetchCuratedPodcasts( $mixId = '', array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'curated_podcasts' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    public function episodes( $mixId, array $arrOptions = [], $boolRecommendations = false  )
    {
        if ( is_array( $mixId ) ) {
            $arrAvailableOptions = [];
            $strResponse = $this->_post( $arrOptions, $arrAvailableOptions, $mixId );
        } else {
            $arrAvailableOptions = [
                'show_transcript',
            ];
            if ( $boolRecommendations ) {
                $arrAvailableOptions[] = 'safe_mode';
            }
            $strResponse = $this->_get( $arrOptions, $arrAvailableOptions, $mixId, $boolRecommendations );
        }
        return $strResponse;
    }

    public function podcasts( $mixId, array $arrOptions = [], $boolRecommendations = false )
    {
        if ( is_array( $mixId ) ) {
            $strResponse = $this->_post( $arrOptions, $arrAvailableOptions, $mixId );
        } else {
            $strResponse = $this->_get( $arrOptions, $arrAvailableOptions, $mixId, $boolRecommendations );
        }
        return $strResponse;
    }

    public function fetchBestPodcasts( array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'best_podcasts' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    public function typeahead( $strQuery = '', array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'typeahead' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    public function search( array $arrOptions = [] )
    {
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( 'search' ) . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    protected function _post( array $arrOptions, array $arrAvailableOptions = [], $strId = '' )
    {
        $strId = $strId ? implode( ',', $strId ) : '';
        $strAction = debug_backtrace()[1]['function'];
        if ( $strId ) {
            $arrOptions['ids'] = $strId;
        }
        $strUrl = $this->getAction( $strAction );
        $strResponse = $this->post( $strUrl, $arrOptions );
        return $strResponse;
    }

    protected function _get( array $arrOptions, $strId = '', $boolRecommendations = false )
    {
        $strId = $strId ? '/' . $strId . ( $boolRecommendations ? '/recommendations' : '' ) : '';
        $strAction = debug_backtrace()[1]['function'];
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( $strAction ) . $strId . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }
}
