<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApiClient;

final class PodcastApiClient extends Client\Curl
{
    protected $_arrSettings = [];

    public function __construct( $strApiKey = '' )
    {
        parent::__construct( $strApiKey );
    }

    public function search( $strQuery = '', $arrOptions = [] )
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
        foreach ( $arrOptions as $strKey => $strValue ) {
            if ( ! in_array( $strKey, $arrAvailableOptions ) ) {
                unset( $arrOptions[$strKey] );
            }
        }

        $strUrl = $this->getAction( 'search' ) . '?' . http_build_query( $arrOptions );
        $strResponse = $this->get( $strUrl );
        return $strResponse;
    }
}
