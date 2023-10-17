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

    function isUploaded(Asset $asset): ?bool
    {
        if(!\imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuEnable) {
            return false;
        }

        $EntryRaw = AssetRecord::find()->where([
			"publisherHandle" => self::$handle,
			"publisherState" => 'completed',
			"assetId" => $asset->id,
		])->one();

		return $EntryRaw ? true : false;
    }

    function getAssetRecord(Asset $asset): ?AssetRecord
    {
        if(!\imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuEnable) {
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