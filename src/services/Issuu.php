<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\services;

use Craft;
use GuzzleHttp;
use craft\elements\Asset;
use imhomedia\publishpdf\records\AssetRecord;
use imhomedia\publishpdf\services\PublishPdfService;

class Issuu extends PublishPdfService
{
    public $client = false;
    public static $handle = 'issuu';

    function __construct() {
        $token = \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuApiKey;
        $this->client = new GuzzleHttp\Client(['headers' => ['X-ACCESS-TOKEN' => $token]]);
    }

    private function _signRequest(array $postVars): array
    {
        $signVars = $postVars;
		ksort($signVars);
		$doc_signature = \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuSecret;
		foreach($signVars as $key=>$value) {
			$doc_signature .= $key.$value;
		}
		
		$postVars['signature'] = md5($doc_signature);
        return $postVars;
    }

    private function _checkResponseError($response): bool|string
    {
        $stream = $response->getBody();
        $contents = json_decode($stream->getContents());
        if(isset($contents->rsp->_content->error)) {
            return "Error code " . $contents->rsp->_content->error->code . " - " . $contents->rsp->_content->error->message;
        }
        return false;
    }

    function getDocuments()
    {
        $query = [
            'action' => 'issuu.documents.list',
            'apiKey' => \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuApiKey,
            'access' => 'public',
            'format' => 'json'
        ];
        $query = $this->_signRequest($query);
        try {
            $response = $this->client->request('GET', 'https://api.issuu.com/1_0', [
                'query' => $query
            ]);
        } catch (\Exception $e) {
            return null;
        }
        $error = $this->_checkResponseError($response);
        if($error !== false) {
            return array(
                'error' => true,
                'msg' => $error
            );
        }
        $stream = $response->getBody();
        $contents = json_decode($stream->getContents());
        Craft::info($contents, 'publishpdfdebug');
        return array(
            'error' => false,
            'documents' => $contents->rsp
        );
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

    public function checkAssetProgress(AssetRecord &$EntryRaw): bool
    {
        return false;
    }
}