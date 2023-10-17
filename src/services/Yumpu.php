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
use imhomedia\publishpdf\records\AssetRecord;

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
        if(!$this->isUploaded($asset)) {
            //TODO: Upload asset to Yumpu and store infos from Yumpu in DB
            $AssetRecord = new AssetRecord();
            $AssetRecord->assetId = $asset->id;
            $AssetRecord->publisherHandle = self::$handle;
            $AssetRecord->publisherState = 'completed';
            $AssetRecord->publisherId = '1234';
            $AssetRecord->publisherResponse = '';
            $AssetRecord->insert();
        }
        return true;
    }

    function deleteAsset(Asset $asset): bool
    {
        return true; //TODO
    }

    function isUploaded(Asset $asset): bool
    {
        if(!\imhomedia\publishpdf\Plugin::getInstance()->getSettings()->yumpuEnable) {
            return false;
        }

        $EntryRaw = AssetRecord::find()->where([
			"publisherHandle" => self::$handle,
			"publisherState" => 'completed',
			"assetId" => $asset->id,
		])->one();

		return $EntryRaw ? true : false;

        //curl -X GET -H "X-ACCESS-TOKEN: YOUR_ACCESS_TOKEN" "https://api.yumpu.com/2.0/document.json?id=27109085"
        // try {
        //     $response = $this->client->request('GET', 'https://api.yumpu.com/2.0/document.json?id=67510611');
        // } catch (\Exception $e) {
        //     return $this->formatResults('-');
        //     return null;
        // }
        // $stream = $response->getBody();
        // $contents = json_decode($stream->getContents());
        // Craft::info($contents, 'publishpdfdebug');
        
        // return true; //TODO
    }

    function getAssetRecord(Asset $asset): ?AssetRecord
    {
        if(!\imhomedia\publishpdf\Plugin::getInstance()->getSettings()->yumpuEnable) {
            return null;
        }

        $EntryRaw = AssetRecord::find()->where([
			"publisherHandle" => self::$handle,
			"assetId" => $asset->id,
		])->one();

		return $EntryRaw ? $EntryRaw : null;
    }
    /**
     * check if an asset is already uploaded to Yumpu
     */
    function isAssetUploaded(Asset $asset): ?string
    {
        if($this->isUploaded($asset)) {
            return $this->formatResults(Craft::t('imhomedia-publishpdf', 'yes'));
        }
        return $this->formatResults(Craft::t('imhomedia-publishpdf', 'no'));
    }

    /**
     * format the result
     */
    function formatResults($string): string
    {
        return $string;
    }
    
}