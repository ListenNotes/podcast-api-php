<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApiClient\Client;
use ListenNotes\PodcastApiClient\Exception;

class Curl
{
    protected $_curl;
    protected $_objInfo;
    protected $_strHeader;
    protected $_strBody;
    protected $_strHost;
    protected $_strMethod;
    protected $_strUri;
    protected $_strVersion = 'api/v2';
    protected $_arrRequestHeaders = [];

    public function __construct( $strApiKey = '' )
    {
        $this->_strHost = 'https://listen-api-test.listennotes.com';
        if ( $strApiKey ) {
            $this->_strHost = 'https://listen-api.listennotes.com';
            $this->setRequestHeader( 'X-ListenAPI-Key', $strApiKey );
        }

        $this->_curl = curl_init();
        $arrOptions = array(
            CURLOPT_RETURNTRANSFER => true,   // return web page
            CURLOPT_HEADER         => true,  // don't return headers
            CURLOPT_FOLLOWLOCATION => true,   // follow redirects
            CURLOPT_MAXREDIRS      => 3,     // stop after 10 redirects
            CURLOPT_ENCODING       => "",     // handle compressed
            CURLOPT_USERAGENT      => "PHP_API", // name of client
            CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
            CURLOPT_CONNECTTIMEOUT => 10,    // time-out on connect
            CURLOPT_TIMEOUT        => 10,    // time-out on response
            CURLINFO_HEADER_OUT    => true,    // headers sent on request
        );
        curl_setopt_array( $this->_curl, $arrOptions );
        if ( count( $this->getRequestHeaders() ) ) {
            curl_setopt( $this->_curl, CURLOPT_HTTPHEADER, $this->getRequestHeaders() );
        }
    }

    public function getRequestHeader( $strHeader = '' )
    {
        return isset( $this->_arrRequestHeaders[$strHeader] ) ? $this->_arrRequestHeaders[$strHeader] : null;
    }

    public function getRequestHeaders( $strHeader = '' )
    {
        $arrHeaders = [];
        if ( count( $this->_arrRequestHeaders ) ) {
            foreach ( $this->_arrRequestHeaders as $strHeader => $strValue ) {
                $arrHeaders[] = $strHeader . ': ' . $strValue;
            }
        }
        return $arrHeaders;
        // return isset( $this->_arrRequestHeaders[$strHeader] ) ? $this->_arrRequestHeaders[$strHeader] : null;
    }

    public function setRequestHeader( $strHeader, $strValue )
    {
        $this->_arrRequestHeaders[$strHeader] = $strValue;
    }

    public function getAction( $strAction = '' )
    {
        $arrPieces = [ $this->_strHost, $this->_strVersion, $strAction ];
        $strAction = implode( '/', $arrPieces );
        return $strAction;
    }

    public function getStatusCode()
    {
        return $this->_objInfo->http_code;
    }

    public function getHeaders()
    {
        $arrHeaders = array_filter( explode( "\r\n", $this->_strHeader ) );
        $strHead = array_shift( $arrHeaders );
        list( $strProtocol, $intStatusCode ) = explode( ' ', $strHead );
        foreach ( $arrHeaders as $I => $strHeader ) {
            unset( $arrHeaders[$I] );
            list( $strHeader, $strValue ) = explode( ': ', $strHeader );
            $arrHeaders[$strHeader] = $strValue;
        }
        return $arrHeaders;
    }

    public function setMethod( $strMethod )
    {
        $this->_strMethod = $strMethod;
    }

    public function getMethod()
    {
        return $this->_strMethod;
    }

    public function setUri( $strUri )
    {
        $this->_strUri = $strUri;
    }

    public function getUri()
    {
        return $this->_strUri;
    }

    public function parseRequestHeaders()
    {
        $arrHeaders = array_filter( explode( "\r\n", $this->_objInfo->request_header ) );
        $strHead = array_shift( $arrHeaders );
        list( $strMethod, $strUri, $strProtocol ) = explode( ' ', $strHead );
        $this->setMethod( $strMethod );
        $this->setUri( $strUri );

        foreach ( $arrHeaders as $I => $strHeader ) {
            unset( $arrHeaders[$I] );
            list( $strHeader, $strValue ) = explode( ': ', $strHeader );
            $arrHeaders[$strHeader] = $strValue;
        }
        return $arrHeaders;
    }

    public function setResponse( $strResponse )
    {
        $intSize = curl_getinfo( $this->_curl, CURLINFO_HEADER_SIZE );
        $this->_objInfo = (object) curl_getinfo( $this->_curl );
        $this->parseRequestHeaders();

        $this->_strHeader = substr( $strResponse, 0, $intSize );
        $this->_strBody = substr( $strResponse, $intSize );
    }

    public function get( $strUrl )
    {
        curl_setopt( $this->_curl, CURLOPT_URL, $strUrl );
        $strResponse = curl_exec( $this->_curl );

        $this->setResponse( $strResponse );

        if ( $this->getStatusCode() != 200 ) {
            throw new \Exception( 'Invalid status code..' );
        }
        // var_dump( $this->_arrInfo );
        return $this->_strBody;
    }

    public function __destruct()
    {
        curl_close( $this->_curl );
    }
}