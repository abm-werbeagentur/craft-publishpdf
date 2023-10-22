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

class Yumpu extends PublishPdfService
{
    public $client = false;
    public static $handle = 'yumpu';

    function __construct() {
        $token = \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->yumpuApiToken;
        $this->client = new GuzzleHttp\Client(['headers' => ['X-ACCESS-TOKEN' => $token]]);
    }

    public function checkProgress(): void
    {
        $EntriesInProgress = AssetRecord::find()->where([
			"publisherHandle" => self::$handle,
			"publisherState" => 'progress'
		])->all();
        
        foreach($EntriesInProgress as $AssetRecord) {
            
            try {
                $response = $this->client->request('GET', 'https://api.yumpu.com/2.0/document/progress.json', [
                    'query' => [
                        'id' => $AssetRecord->publisherId
                    ]
                ]);
            } catch (\Exception $e) {
                continue;
            }
            $stream = $response->getBody();
            $contents = json_decode($stream->getContents());
            

            if(isset($contents->document[0]->id)) {
                $AssetRecord->publisherState = 'completed';
                $AssetRecord->publisherId = $contents->document[0]->id;
                $AssetRecord->publisherResponse = json_encode($contents);
                $AssetRecord->update();
            }
        }
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
            //Craft::info($e, 'publishpdfdebug');
            return null;
        }
        $stream = $response->getBody();
        $contents = json_decode($stream->getContents());
        return $contents;
    }

    function uploadAsset(Asset $asset): bool|string
    {
        $do_upload = true;
        if($asset->getExtension() !== 'pdf') {
            return Craft::t('imhomedia-publishpdf', 'Only pdf files can be uploaded to yumpu');
        }
        if($this->isUploaded($asset)) {
            //TODO: check isUploaded ... yumpu upload older than asset ... then upload
            $do_upload = false;
        }

        if($do_upload) {
            //Upload asset to Yumpu and store infos from Yumpu in DB
            try {
                $response = $this->client->request('POST', 'https://api.yumpu.com/2.0/document/file.json', [
                    'multipart' => [
                        [
                            'name' => 'file',
                            'contents' => $asset->getContents(),
                            'filename' => $asset->filename
                        ],
                        [
                            'name' => 'title',
                            'contents' => $asset->title
                        ]
                    ]
                ]);
            } catch(\Exception $e) {
                return $this->handleYumpuException($e);
            }
            
            $stream = $response->getBody();
            $contents = json_decode($stream->getContents());

            if($contents->state == 'success') {
                Craft::info($contents->progress_id, 'publishpdfdebug');
                Craft::info(json_encode($contents), 'publishpdfdebug');
                $AssetRecord = new AssetRecord();
                $AssetRecord->assetId = $asset->id;
                $AssetRecord->publisherHandle = self::$handle;
                $AssetRecord->publisherState = 'progress';
                $AssetRecord->publisherId = $contents->progress_id;
                $AssetRecord->publisherResponse = json_encode($contents);
                $AssetRecord->insert();
                return true;
            }
        }
        return Craft::t('imhomedia-publishpdf', 'Asset already uploaded to yumpu');
    }

    function deleteAsset(Asset $asset): bool|string
    {
        $AssetRecord = $this->getAssetRecord($asset);
        if($AssetRecord) {
            try {
                $this->client->request('DELETE', 'https://api.yumpu.com/2.0/document.json', [
                    'form_params' => [
                        'id' => $AssetRecord->publisherId
                    ]
                ]);
            } catch (\Exception $e) {
                return $this->handleYumpuException($e);
            }
            $AssetRecord->delete();
            return true;
        }
        return Craft::t('imhomedia-publishpdf', 'Asset not present on yumpu');
        
    }

    function isUploaded(Asset $asset): bool
    {
        if(!\imhomedia\publishpdf\Plugin::getInstance()->getSettings()->yumpuEnable) {
            return false;
        }

        $EntryRaw = AssetRecord::find()->where([
			"publisherHandle" => self::$handle,
			//"publisherState" => 'completed',
			"assetId" => $asset->id,
		])->one();

        if($EntryRaw) {
            if($EntryRaw->publisherState == 'progress') {
                try {
                    $response = $this->client->request('GET', 'https://api.yumpu.com/2.0/document/progress.json', [
                        'query' => [
                            'id' => $EntryRaw->publisherId
                        ]
                    ]);
                } catch (\Exception $e) {
                    return false;
                }
                $stream = $response->getBody();
                $contents = json_decode($stream->getContents());
                Craft::info("1:" . gettype($contents), 'publishpdfdebug');
                Craft::info("2:" . gettype($contents->document), 'publishpdfdebug');
                Craft::info($contents->document, 'publishpdfdebug');
                
                if(isset($contents->document[0]->id)) {
                    $EntryRaw->publisherState = 'completed';
                    $EntryRaw->publisherId = $contents->document[0]->id;
                    $EntryRaw->publisherResponse = json_encode($contents);
                    $EntryRaw->publisherUrl = $contents->document[0]->url;
                    $EntryRaw->publisherEmbedCode = $contents->document[0]->embed_code;
                    $EntryRaw->update();
                    Craft::info('update', 'publishpdfdebug');
                    return true;
                }
            } else if($EntryRaw->publisherState == 'completed') {
                return true;
            }
        }
        return false;
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
    
}