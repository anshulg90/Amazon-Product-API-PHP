<?php
/**
 * PHP Wrapper for Amaozon Product API.
 * GitHub: https://github.com/anshulg90/amazon-product-api-php
 * License: MIT License
 *
 * @author Anshul G. ( @anshulg90, anshulganrade90@gmail.com)
 * @version 1.0
**/

namespace AmazonLib;

class Amazon
{ 

  //Access key, Secret key And Affiliate ID or associate tag are entered through the constructor.
  private $access_key;
  private $secret_key;
  private $associate_tag;
  public $common_params;

  private $amazon_base_url = "webservices.amazon.in";
  private $endpoint = '/onca/xml';
  private $verify_ssl = false;


  /**
   * Obtains the values for required variables during initialization.
   *
   * @param string $access_key Access key for the API.
   * @param string $secret_key Secret key for the API.
   * @param string $associate_tag Your Associate Tag or Affiliate Id.
   * @return void
  **/
  public function __construct( $access_key, $secret_key, $associate_tag )
  {
    $this->access_key    = $access_key;
    $this->secret_key    = $secret_key;
    $this->associate_tag = $associate_tag;
    $this->common_params =  [
                              'Service' => 'AWSECommerceService',
                              'AssociateTag' => $associate_tag,
                              'AWSAccessKeyId' => $access_key,
                            ];
  }


  /**
   * Aenerating Signature for Amazon Request.
   * The generateSignature method is responsible for generating the signature required by the API.
   *
   * @param array $query This merges the array of common parameters with the argument that was passed to it.
   * @return string Generate a keyed hash value.
  **/
  private function generateSignature($query)
  {
    ksort($query);
    $sign           = http_build_query($query);
    $request_method = 'GET';
    $string_to_sign = "{$request_method}\n{$this->amazon_base_url}\n{$this->endpoint}\n{$sign}";
    $signature      = base64_encode(
                        hash_hmac("sha256", $string_to_sign, $this->secret_key, true)
                      );
    return $signature;
  }


  /**
   * Sends the HTTP request using cURL.
   * 
   * @param array $query This merges the array of common parameters with the argument that was passed to it.
   * @param int $timeout Timeout before the request is cancelled.(Default timeout is 30 sec)
   * @return string Response from the API
  **/
  private function sendRequest( $query, $timeout=30 )
  {
    $timestamp          = date('c');
    $query['Timestamp'] = $timestamp;
    $query              = array_merge($this->common_params, $query);
    $query['Signature'] = $this->generateSignature($query);

    $canonicalized_query = array();

    foreach( $query as $param => $value )
    {
        $param = str_replace("%7E", "~", rawurlencode($param));
        $value = str_replace("%7E", "~", rawurlencode($value));
        $canonicalized_query[] = $param."=".$value;
    }

    $canonicalized_query = implode("&", $canonicalized_query);

    //create request url
    $request_url = "http://".$this->amazon_base_url.$this->endpoint."?".$canonicalized_query;

    //Make sure cURL is available
    if (function_exists('curl_init') && function_exists('curl_setopt'))
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $request_url);
      curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $xml_response = curl_exec($ch);

      if ($xml_response === False){
        return False;
      }
      else{
        /* parse XML */
        $parsed_xml = @simplexml_load_string($xml_response);
        return ($parsed_xml === False) ? False : $parsed_xml;
      }
      //close curl
      curl_close($ch);        
    }else{
      // Cannot work without cURL
      return false;
    }
  }


  /**
   * Caterogies on amazon seachindex option.
   * 
   * @return array Return amazone search index option.
  **/
  public function getSearchIndices()
  {
    return [
      'All',
      'UnboxVideo',
      'Appliances',
      'MobileApps',
      'ArtsAndCrafts',
      'Automotive',
      'Books',
      'Music',
      'Wireless',
      'Collectibles',
      'PCHardware',
      'Electronics',
      'KindleStore',
      'Movies',
      'OfficeProducts',
      'Software',
      'Tools',
      'VideoGames'
    ];
  }


  /**
   * Sends a request to search Item on Amazon.
   * 
   * @param string $keywords Searching Item Name.
   * @param string $search_index Amazon Product Category.
   * @param int $page page index no for pagination.
   * @return array Response from the API
  **/
  public function itemSearch( $keywords, $search_index, $page=1 )
  {
    $query = [
      'ResponseGroup' => 'Medium',
      'Operation'   => 'ItemSearch',
      'Keywords'    => urlencode($keywords),
      'SearchIndex' => $search_index,
      "ItemPage"    => $page
    ];

    $response = $this->sendRequest($query);
    return $response;
  }


  /**
   * Sends a requet to get Single Item Details.
   * 
   * @param string $itemId A number that uniquely identifies an item. The number is specified by the parameter IdType.
   * @param string $idType Item identifier type.( ASIN, SKU, UPC, EAN, ISBN) 
   * @return array Response from the API
  **/
  public function itemLookup( $itemId, $idType )
  {
    $query = [
      'ResponseGroup' => 'Large,Reviews,EditorialReview',
      'Operation' => 'ItemLookup',
      'ItemId' => $itemId,
      'IdType' => $idType
    ];

    $response = $this->sendRequest($query);
    return $response;
  }


  /**
   * Sends a requet to returns products that are similar to one or more items specified in the request.
   * 
   * @param string $itemId A number that uniquely identifies an item (ASIN Id of Product).
   * @return array Response from the API
  **/
  public function similarityLookup( $itemId )
  {
    $query = [
      'ResponseGroup' => 'Medium',
      'Operation' => 'SimilarityLookup',
      'ItemId' => $itemId,
      "MerchantId" => "Amazon"
    ];

    $response = $this->sendRequest($query);
    return $response;
  }

}
