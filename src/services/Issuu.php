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

class Issuu extends Component
{
    public $client = false;
    public static $handle = 'issuu';

    function __construct() {
        $token = \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuApiToken;
        $this->client = new GuzzleHttp\Client(['headers' => ['X-ACCESS-TOKEN' => $token]]);
    }

    function getDocuments()
    {
        return null;
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