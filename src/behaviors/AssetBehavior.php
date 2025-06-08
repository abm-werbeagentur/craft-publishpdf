<?php

namespace abmat\publishpdf\behaviors;

use Craft;
use yii\base\Behavior;
use craft\elements\Asset;
use abmat\publishpdf\records\AssetRecord;

class AssetBehavior extends Behavior
{
    private $issuuAssetRecord = null;
    private $yumpuAssetRecord = null;

    private function _issuuGetRecord(Asset $asset): ?AssetRecord
    {
        if($this->issuuAssetRecord == null) {
            $AssetRecord = \abmat\publishpdf\Plugin::getInstance()->issuu->getAssetRecord($asset);
            $this->issuuAssetRecord = $AssetRecord;
        }
        return $this->issuuAssetRecord;
    }

    public function issuuIsUploaded(): bool
    {
        //return \abmat\publishpdf\Plugin::getInstance()->issuu->isUploaded($this->owner);
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            return true;
        }
        return false;
    }

    public function issuuGetId(): ?string
    {
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            return $AssetRecord->publisherId;
        }
        return null;
    }

    public function issuuGetLink(): ?string
    {
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            return $AssetRecord->publisherUrl;
        }
        return null;
    }
    private function _getUsername(): ?string
    {
        if (preg_match('/issuu\.com\/([^\/]+)\/docs/', $this->issuuGetLink(), $matches)) {
            $username = $matches[1];
            return $username;
        } else {
            return null;
        }
    }
    public function issuuGetEmbedUrl(): ?string
    {
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            $username = $this->_getUsername();
            $id = $this->issuuGetId();
            if($username && $id) {
                return "https://e.issuu.com/embed.html?d=".$id."&u=".$username;
            }
            //return $AssetRecord->publisherEmbedUrl;
        }
        return null;
    }

    public function issuuGetEmbedCode(): ?string
    {
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            return '
                <iframe 
                    allow="clipboard-write" 
                    sandbox="allow-top-navigation allow-top-navigation-by-user-activation allow-downloads allow-scripts allow-same-origin allow-popups allow-modals allow-popups-to-escape-sandbox allow-forms" 
                    allowfullscreen="true" 
                    src="'.$this->issuuGetEmbedUrl().'">
                </iframe>';
            // return $AssetRecord->publisherEmbedCode;
        }
        return null;
    }



    private function _yumpuGetRecord(Asset $asset): ?AssetRecord
    {
        if($this->yumpuAssetRecord == null) {
            $AssetRecord = \abmat\publishpdf\Plugin::getInstance()->yumpu->getAssetRecord($asset);
            $this->yumpuAssetRecord = $AssetRecord;
        }
        
        return $this->yumpuAssetRecord;
    }
    public function yumpuIsUploaded(): bool
    {
        // return \abmat\publishpdf\Plugin::getInstance()->yumpu->isUploaded($this->owner);
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return true;
        }
        return false;
    }

    public function yumpuGetId(): ?string
    {
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return $AssetRecord->publisherId;
        }
        return null;
    }
    
    public function yumpuGetLink(): ?string
    {
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return $AssetRecord->publisherUrl;
        }
        return null;
    }

    public function yumpuGetEmbedUrl(): ?string
    {
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return $AssetRecord->publisherEmbedUrl;
        }
        return null;
    }

    public function yumpuGetEmbedCode(): ?string
    {
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return $AssetRecord->publisherEmbedCode;
        }
        return null;
    }
}

?>