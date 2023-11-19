<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\services;

use Craft;
use GuzzleHttp;
use craft\elements\Asset;
use craft\helpers\UrlHelper;
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

    private function _checkResponseError($contents): bool|string
    {
        //Craft::info($contents->rsp, 'publishpdfdebug');
        if($contents && isset($contents->rsp->_content->error)) {
            return "Error code " . $contents->rsp->_content->error->code . " - " . $contents->rsp->_content->error->message;
        }
        return false;
    }

    function getDocuments()
    {
        $query = [
            'action' => 'issuu.documents.list',
            'apiKey' => \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuApiKey,
            'pageSize' => 30,
            'documentSortBy' => 'publishDate',
            'resultOrder' => 'desc',
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
        $stream = $response->getBody();
        $contents = json_decode($stream->getContents());
        $error = $this->_checkResponseError($contents);
        if($error !== false) {
            return array(
                'error' => true,
                'msg' => $error
            );
        }
        
        // Craft::info($contents, 'publishpdfdebug');
        if($contents && isset($contents->rsp)) {
            return array(
                'error' => false,
                'documents' => $contents->rsp
            );
        }
        return array('error' => true, 'msg'=>'No documents found');
    }

    function uploadAsset(Asset $asset): bool|string
    {
        $do_upload = true;
        if(!in_array($asset->getExtension(), array('pdf', 'doc', 'docx'))) {
            return Craft::t('imhomedia-publishpdf', 'Only pdf, doc, docx files can be uploaded to issuu');
        }
        if($this->isUploaded($asset)) {
            //TODO: check isUploaded ... issuu upload older than asset ... then upload
            $do_upload = false;
        }

        if($do_upload) {
            $query = [
                'action' => 'issuu.document.upload',
                'apiKey' => \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuApiKey,
                'title' => $asset->filename,
                'commentsAllowed' => 'false',
                'downloadable' => 'false',
                'access' => 'public',
                'ratingsAllowed' => 'false',
                'format' => 'json'
            ];
            $query = $this->_signRequest($query);

            try {
                $multipart = array();
                $multipart[] = array(
                    'name' => 'file',
                    'contents' => $asset->getContents(),
                    'filename' => $asset->filename
                );
                foreach($query as $key => $value) {
                    $multipart[] = array(
                        'name' => $key,
                        'contents' => $value
                    );
                }
                // Craft::info('multipart', 'publishpdfdebug');
                // Craft::info(json_encode($multipart), 'publishpdfdebug');
                
                $response = $this->client->request('POST', 'http://upload.issuu.com/1_0', [
                    'multipart' => $multipart,
                    //'query' =>  $query
                ]);
            } catch(\Exception $e) {
                Craft::info($e, 'publishpdfdebug');
                return $this->handleIssuuException($e);
            }

            // Craft::info('response', 'publishpdfdebug');
            // Craft::info($response, 'publishpdfdebug');
            // Craft::info("getStatusCode:".$response->getStatusCode(), 'publishpdfdebug');
            // Craft::info("getReasonPhrase:".$response->getReasonPhrase(), 'publishpdfdebug');

            $stream = $response->getBody();

            // Craft::info('stream:', 'publishpdfdebug');
            // Craft::info($stream, 'publishpdfdebug');

            $contents = json_decode($stream->getContents());

            // Craft::info('contents', 'publishpdfdebug');
            // Craft::info($contents, 'publishpdfdebug');

            if($contents->rsp->stat == 'ok') {
                $AssetRecord = new AssetRecord();
                $AssetRecord->assetId = $asset->id;
                $AssetRecord->publisherHandle = self::$handle;
                $AssetRecord->publisherState = 'completed';
                $AssetRecord->publisherId = $contents->rsp->_content->document->name;
                $AssetRecord->publisherResponse = json_encode($contents);
                $url = "https://issuu.com/".\imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuUsername."/docs/".$contents->rsp->_content->document->name;
                $AssetRecord->publisherUrl = $url;

                $embedUrl = 'https://issuu.com/'.\imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuUsername.'/docs/'.$contents->rsp->_content->document->name.'?mode=window&printButtonEnabled=false';
                $AssetRecord->publisherEmbedUrl = $embedUrl;
                
                $embedCode =  '<iframe allow="clipboard-write" 
                    sandbox="allow-top-navigation allow-top-navigation-by-user-activation allow-downloads allow-scripts allow-same-origin allow-popups allow-modals allow-popups-to-escape-sandbox allow-forms" 
                    allowfullscreen="true"
                    src="'.$embedUrl.'">
                </iframe>';

                $AssetRecord->publisherEmbedCode = $embedCode;
                $AssetRecord->insert();
                return true;
            } else {
                if(isset($contents->rsp->_content->error->message)) {
                    return (string) ($contents->rsp->_content->error->code . " - " . $contents->rsp->_content->error->message);
                }
                return 'Error #1';
            }
        }
        return Craft::t('imhomedia-publishpdf', 'Asset already uploaded to issuu');
    }

    function deleteAsset(Asset $asset): bool|string
    {
        $AssetRecord = $this->getAssetRecord($asset);
        if($AssetRecord && $AssetRecord->publisherState == 'completed') {
            $query = [
                'action' => 'issuu.document.delete',
                'apiKey' => \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuApiKey,
                'names' => $AssetRecord->publisherId,
                'format' => 'json'
            ];
            $query = $this->_signRequest($query);
            //Upload asset to Issuu and store infos from Issuu in DB
            try {
                $response = $this->client->request('DELETE', 'https://api.issuu.com/1_0', [
                    'query' => $query
                ]);
            } catch(\Exception $e) {
                return $this->handleIssuuException($e);
            }

            $stream = $response->getBody();
            $contents = json_decode($stream->getContents());
            if($contents->rsp->stat == 'ok') {
                $AssetRecord->delete();
                return true;
            } else {
                if(isset($contents->rsp->_content->error->message)) {
                    return (string) ($contents->rsp->_content->error->code . " - " . $contents->rsp->_content->error->message);
                }
                return 'Error #1';
            }
        }
        return Craft::t('imhomedia-publishpdf', 'Asset not present on issuu');
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