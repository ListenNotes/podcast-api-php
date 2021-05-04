<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApiClient;

final class PodcastApiClient extends Client\Curl
{
    public function __construct( $strApiKey = '' )
    {
        parent::__construct( $strApiKey );
    }

    public function curated_podcasts( $mixId, array $arrOptions = [] )
    {
        $arrAvailableOptions = [];
        $strResponse = $this->_get( $arrOptions, $arrAvailableOptions, $mixId );
        return $strResponse;
    }

    public function episodes( $mixId, array $arrOptions = [] )
    {
        if ( is_array( $mixId ) ) {
            $arrAvailableOptions = [];
            $strResponse = $this->_post( $arrOptions, $arrAvailableOptions, $mixId );
        } else {
            $arrAvailableOptions = [
                'show_transcript',
            ];
            $strResponse = $this->_get( $arrOptions, $arrAvailableOptions, $mixId );
        }
        return $strResponse;
    }

    public function podcasts( $mixId, array $arrOptions = [] )
    {
        if ( is_array( $mixId ) ) {
            $arrAvailableOptions = [
                'rsses',
                'itunes_ids',
                'show_latest_episodes',
                'next_episode_pub_date'
            ];
            $strResponse = $this->_post( $arrOptions, $arrAvailableOptions, $mixId );
        } else {
            $arrAvailableOptions = [
                'next_episode_pub_date',
                'sort'
            ];
            $strResponse = $this->_get( $arrOptions, $arrAvailableOptions, $mixId );
        }
        return $strResponse;
    }

    public function best_podcasts( array $arrOptions = [] )
    {
        $arrAvailableOptions = [
            'genre_id',
            'page',
            'region',
            'safe_mode'
        ];
        $strResponse = $this->_get( $arrOptions, $arrAvailableOptions );
        return $strResponse;
    }

    public function typeahead( $strQuery = '', array $arrOptions = [] )
    {
        $arrOptions['q'] = $strQuery;
        $arrAvailableOptions = [
            'q',
            'show_podcasts',
            'show_genres',
            'safe_mode'
        ];
        $strResponse = $this->_get( $arrOptions, $arrAvailableOptions );
        return $strResponse;
    }

    public function search( $strQuery = '', array $arrOptions = [] )
    {
        $arrOptions['q'] = $strQuery;
        $arrAvailableOptions = [
            'q',
            'sort_by_date',
            'type',
            'offset',
            'len_min',
            'len_max',
            'episode_count_min',
            'episode_count_max',
            'genre_ids',
            'published_before',
            'published_after',
            'only_in',
            'language',
            'region',
            'ocid',
            'ncid',
            'safe_mode',
        ];
        $strResponse = $this->_get( $arrOptions, $arrAvailableOptions );
        return $strResponse;
    }

    protected function _post( array $arrOptions, array $arrAvailableOptions, $strId = '' )
    {
        $strId = $strId ? implode( ',', $strId ) : '';
        $strAction = debug_backtrace()[1]['function'];
        $arrOptions = $this->_fixOptions( $arrOptions, $arrAvailableOptions );
        if ( $strId ) {
            $arrOptions['ids'] = $strId;
        }
        $strUrl = $this->getAction( $strAction );
        $strResponse = $this->post( $strUrl, $arrOptions );
        return $strResponse;
    }

    protected function _get( array $arrOptions, array $arrAvailableOptions, $strId = '' )
    {
        $strId = $strId ? '/' . $strId : '';
        $strAction = debug_backtrace()[1]['function'];
        $arrOptions = $this->_fixOptions( $arrOptions, $arrAvailableOptions );
        $strQuery = count( $arrOptions ) ? '?' . http_build_query( $arrOptions ) : '';
        $strUrl = $this->getAction( $strAction ) . $strId . $strQuery;
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }

    protected function _fixOptions( array $arrOptions = [], array $arrAvailableOptions = [] )
    {
        foreach ( $arrOptions as $strKey => $strValue ) {
            if ( ! in_array( $strKey, $arrAvailableOptions ) ) {
                unset( $arrOptions[$strKey] );
            }
        }
        return $arrOptions;
    }
}
