<?php
/**
 * @link https://abm.at
 * @copyright Copyright (c) abm Feregyhazy & Simon GmbH
*/

namespace abmat\publishpdf\services;

use Craft;
use GuzzleHttp;
use craft\elements\Asset;
use craft\helpers\UrlHelper;
use abmat\publishpdf\records\AssetRecord;
use abmat\publishpdf\services\PublishPdfService;

class Issuu extends PublishPdfService
{
    public $client = false;
    public static $handle = 'issuu';
    protected $client_id = "";
    protected $client_secret = "";
    protected $access_token = "";
    protected $httpClient = null;
    private $token_url = "https://oauth.issuu.com/oauth2/token";

    function __construct() {
        $this->client_id = \abmat\publishpdf\Plugin::getInstance()->getSettings()->getIssuuClientId();
        $this->client_secret = \abmat\publishpdf\Plugin::getInstance()->getSettings()->getIssuuClientSecret();

        $this->httpClient = new GuzzleHttp\Client();

        $this->grantCredentials();
    }

    private function grantCredentials() {
		$response = $this->httpClient->post(
			$this->token_url,
			[
				'form_params' => [
					'grant_type' => 'client_credentials',
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
					'scope' => 'document:read document:write',
				]
			]
		);

		if($response->getStatusCode()==200) {
			$body = json_decode($response->getBody()->getContents());
			if(isset($body->access_token)) {
				$this->access_token = $body->access_token;
			}
		}

		if(!$this->access_token) {
			throw new \Exception("failed auth with client_credentials");
		}
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
        try {

			$response = $this->httpClient->get(
				"https://api.issuu.com/v2/publications",
				[
					'headers' => [
						'Authorization' => 'Bearer '.$this->access_token,
					],
					'query' => [
                        'size' => 50,
                        'state' => 'PUBLISHED'
                    ]
				]
			);
		} catch(GuzzleHttp\Exception\ClientException $e) {
			return array(
                'error' => true,
                'msg' => $e->getMessage()
            );
		
		} catch(GuzzleHttp\Exception\ServerException $e) {
            return array(
                'error' => true,
                'msg' => $e->getMessage()
            );
		}

		if($response->getStatusCode()==200) {
			$body = json_decode($response->getBody()->getContents());
            return array(
                'error' => false,
                'documents' => $body
            );
		}

		return array(
            'error' => true,
            'msg' => $response->getStatusCode()
        );
    }

    public function publishDraft($slug) {
		try {
			$response = $this->httpClient->post(
				"https://api.issuu.com/v2/drafts/".$slug."/publish",
				[
					'headers' => [
						'Authorization' => 'Bearer '.$this->access_token,
					],
					'json' => [
						'desiredName' => $slug,
					],
				]
			);
		} catch(GuzzleHttp\Exception\ClientException $e) {
            if($e->getCode() == 404) {
                //delete entry
                $AssetRecord = AssetRecord::find()->where([
                    "publisherHandle" => self::$handle,
                    "publisherId" => $slug,
                ])->one();
                if($AssetRecord) {
                    $AssetRecord->delete();
                }
            }
			//throw new \Exception($e->getMessage());
            return false;

		} catch(GuzzleHttp\Exception\ServerException $e) {
			//throw new \Exception($e->getMessage());
            return false;
		}

		if($response->getStatusCode()==200) {
			$body = json_decode($response->getBody()->getContents());
			if(isset($body->publicLocation) && $body->publicLocation!="") {
				return $body;
			}
		}

		return false;
	}

    function uploadAsset(Asset $asset): bool|string
    {
        $do_upload = true;
        if(!in_array($asset->getExtension(), array('pdf', 'doc', 'docx'))) {
            return Craft::t('abm-publishpdf', 'Only pdf, doc, docx files can be uploaded to issuu');
        }
        if($this->isUploaded($asset)) {
            //TODO: check isUploaded ... issuu upload older than asset ... then upload
            $do_upload = false;
        }

        if($do_upload) {
            $pdf_title = str_replace("  "," ",preg_replace("/[^a-zA-Z0-9_.\-\s]/","",trim($asset->filename))) . " -".$asset->id;

            try {
                $response = $this->httpClient->post(
                    "https://api.issuu.com/v2/drafts",
                    [
                        'headers' => [
                            'Authorization' => 'Bearer '.$this->access_token,
                        ],
                        'json' => [
                            'confirmCopyright' => true,
                            //'fileUrl' => $asset->getDataUrl(),
                            'info' => [
                                'file' => 0,
                                'access' => 'PUBLIC',
                                'title' => $pdf_title,
                                'description' => "",
                                'preview' => false,
                                'type' => 'promotional',
                                'showDetectedLinks' => false,
                                'downloadable' => true,
                                'originalPublishDate' => null
                            ]
                        ],
                    ]
                );
    
            } catch(GuzzleHttp\Exception\ClientException $e) {
                throw new \Exception("Client failed uploading asset to Issuu: " . $e->getMessage());
    
            } catch(GuzzleHttp\Exception\ServerException $e) {
                throw new \Exception("Server failed uploading asset to Issuu: " . $e->getMessage());
            }
            
            $slug = "";
            if($response->getStatusCode()==200) {
                $body = json_decode($response->getBody()->getContents());
                if(isset($body->slug) && $body->slug!="") {
                    $slug = $body->slug;
                }
            }
    
            if($slug) {
                try {
                    $multipart = array();
                    $multipart[] = array(
                        'name' => 'file',
                        'contents' => $asset->getContents(),
                        'filename' => $asset->filename
                    );
                    // Craft::info('multipart', 'publishpdfdebug');
                    // Craft::info(json_encode($multipart), 'publishpdfdebug');
                    
                    $response = $this->httpClient->PATCH(
                        'https://api.issuu.com/v2/drafts/'.$slug.'/upload',
                        [
                            'headers' => [
                                'Authorization' => 'Bearer '.$this->access_token,
                            ],
                            'multipart' => $multipart
                        ]
                    );
                } catch(\Exception $e) {
                    Craft::info($e, 'publishpdfdebug');
                    return $this->handleIssuuException($e);
                }

                if($response->getStatusCode()==200) {
                    $AssetRecord = new AssetRecord();
                    $AssetRecord->assetId = $asset->id;
                    $AssetRecord->publisherHandle = self::$handle;
                    $AssetRecord->publisherState = 'progress';
                    $AssetRecord->publisherId = $slug;
                    $AssetRecord->insert();
                    return true;
                }
            }
            throw new \Exception("Asset upload to Issuu failed");
        }
        return Craft::t('abm-publishpdf', 'Asset already uploaded to Issuu');
    }

    function deleteAsset(Asset $asset): bool|string
    {
        $AssetRecord = $this->getAssetRecord($asset);
        if($AssetRecord && $AssetRecord->publisherId !== '') {
            $slug = $AssetRecord->publisherId;

            if($slug) {
                try {
                    $response = $this->httpClient->delete(
                        "https://api.issuu.com/v2/publications/".$slug,
                        [
                            'headers' => [
                                'Authorization' => 'Bearer '.$this->access_token,
                            ],
                        ]
                    );
                } catch(GuzzleHttp\Exception\ClientException $e) {
                    if($e->getCode() == 404) {
                        $AssetRecord->delete();
                    }
                    throw new \Exception("Asset delete error #1: ".$e->getMessage());
                    return false;
                    
                } catch(GuzzleHttp\Exception\ServerException $e) {
                    throw new \Exception("Asset delete error #2 ".$e->getMessage());
                    return false;
                }
                $AssetRecord->delete();
                return true;
            }
            $AssetRecord->delete();
            return true;
        }
        return Craft::t('abm-publishpdf', 'Asset not present on issuu');
    }

    function replaceAsset(Asset $asset): bool|string
    {
        $this->deleteAsset($asset);
        return $this->uploadAsset($asset);
    }

    function isUploaded(Asset $asset): ?bool
    {
        if(!\abmat\publishpdf\Plugin::getInstance()->getSettings()->issuuEnable) {
            return false;
        }

        $EntryRaw = AssetRecord::find()->where([
			"publisherHandle" => self::$handle,
			//"publisherState" => 'completed',
			"assetId" => $asset->id,
		])->one();

        if($EntryRaw) {
            if($EntryRaw->publisherState == 'progress') {
                if($this->checkAssetProgress($EntryRaw)) {
                    return true;
                }
                return false;
            } else if($EntryRaw->publisherState == 'completed') {
                return true;
            }
        }
        return false;
    }

    function getAssetRecord(Asset $asset): ?AssetRecord
    {
        if(!\abmat\publishpdf\Plugin::getInstance()->getSettings()->issuuEnable) {
            return null;
        }

        $EntryRaw = AssetRecord::find()->where([
			"publisherHandle" => self::$handle,
			"assetId" => $asset->id,
		])->one();

		return $EntryRaw ? $EntryRaw : null;
    }

    public function checkAssetProgress(AssetRecord &$AssetRecord): bool
    {
        $slug = $AssetRecord->publisherId;

        $body = $this->publishDraft($slug);

        if(is_object($body) && isset($body->publicLocation)) {
            $AssetRecord->publisherResponse = json_encode($body);
            $url = $body->publicLocation;
            $AssetRecord->publisherUrl = $url;

            $embedUrl = $url.'?mode=window&printButtonEnabled=false';
            $AssetRecord->publisherEmbedUrl = $embedUrl;
            
            $embedCode =  '<iframe allow="clipboard-write" 
                sandbox="allow-top-navigation allow-top-navigation-by-user-activation allow-downloads allow-scripts allow-same-origin allow-popups allow-modals allow-popups-to-escape-sandbox allow-forms" 
                allowfullscreen="true"
                src="'.$embedUrl.'">
            </iframe>';

            $AssetRecord->publisherEmbedCode = $embedCode;
            $AssetRecord->publisherState = 'completed';
            $AssetRecord->update();
            return true;
        }
        return false;
    }
}