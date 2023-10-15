<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\services;

use Craft;
use GuzzleHttp;
use craft\base\Component;
use craft\elements\Asset;

class Yumpu extends Component
{
    public $client = false;
    public static $handle = 'yumpu';

    function __construct() {
        $token = \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->yumpuApiToken;
        $this->client = new GuzzleHttp\Client(['headers' => ['X-ACCESS-TOKEN' => $token]]);
    }

    function getCategories()
    {
        //curl -X GET -H "X-ACCESS-TOKEN: YOUR_ACCESS_TOKEN" "https://api.yumpu.com/2.0/document/categories.json"
        try {
            $response = $this->client->request('GET', 'https://api.yumpu.com/2.0/document/categories.json');
        } catch (\Exception $e) {
            return null;
        }
        $stream = $response->getBody();
        $contents = json_decode($stream->getContents());
        return $contents;
    }

    function getDocuments()
    {
        //curl -X GET -H "X-ACCESS-TOKEN: YOUR_ACCESS_TOKEN" "https://api.yumpu.com/2.0/documents.json?offset=0&limit=1&sort=desc"
        try {
            $response = $this->client->request('GET', 'https://api.yumpu.com/2.0/documents.json');
        } catch (\Exception $e) {
            return null;
        }
        $stream = $response->getBody();
        $contents = json_decode($stream->getContents());
        return $contents;
    }

    function uploadAsset(Asset $asset): bool
    {
        return true;
    }

    function deleteAsset(Asset $asset): bool
    {
        return true;
    }

    /**
     * check if an asset is already uploaded to Yumpu
     */
    function isAssetUploaded(Asset $asset): ?string
    {
        //curl -X GET -H "X-ACCESS-TOKEN: YOUR_ACCESS_TOKEN" "https://api.yumpu.com/2.0/document.json?id=27109085"
        try {
            $response = $this->client->request('GET', 'https://api.yumpu.com/2.0/document.json?id=67510611');
        } catch (\Exception $e) {
            return $this->formatResults('-');
            return null;
        }
        $stream = $response->getBody();
        $contents = json_decode($stream->getContents());
        Craft::info($contents, 'publishpdfdebug');

        return $this->formatResults('yes/no');
    }

    /**
     * format the result
     */
    function formatResults($string): string
    {
        return $string;
    }
    
}