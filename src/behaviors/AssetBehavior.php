<?php

namespace imhomedia\publishpdf\behaviors;

use Craft;
use yii\base\Behavior;
use craft\elements\Asset;
use imhomedia\publishpdf\records\AssetRecord;

class AssetBehavior extends Behavior
{
    private $issuuAssetRecord = null;
    private $yumpuAssetRecord = null;

    private function _issuuGetRecord(Asset $asset): ?AssetRecord
    {
        if($this->issuuAssetRecord == null) {
            $AssetRecord = \imhomedia\publishpdf\Plugin::getInstance()->issuu->getAssetRecord($asset);
            $this->issuuAssetRecord = $AssetRecord;
        }
        return $this->issuuAssetRecord;
    }

    public function issuuIsUploaded(): bool
    {
        //return \imhomedia\publishpdf\Plugin::getInstance()->issuu->isUploaded($this->owner);
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            return true;
        }
        return false;
    }

    public function issuuGetId(): ?int
    {
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            return $AssetRecord->publisherId;
        }
        return null;
    }

    public function issuuGetLink(): ?string
    {
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            return 'issuu link for publisherId '.$AssetRecord->publisherId;
        }
        return null;
    }

    public function issuuGetEmbedLink(): ?string
    {
        if($AssetRecord = $this->_issuuGetRecord($this->owner)) {
            return 'issuu embed link for publisherId '.$AssetRecord->publisherId;
        }
        return null;
    }



    private function _yumpuGetRecord(Asset $asset): ?AssetRecord
    {
        if($this->yumpuAssetRecord == null) {
            $AssetRecord = \imhomedia\publishpdf\Plugin::getInstance()->yumpu->getAssetRecord($asset);
            $this->yumpuAssetRecord = $AssetRecord;
        }
        
        return $this->yumpuAssetRecord;
    }
    public function yumpuIsUploaded(): bool
    {
        // return \imhomedia\publishpdf\Plugin::getInstance()->yumpu->isUploaded($this->owner);
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return true;
        }
        return false;
    }

    public function yumpuGetId(): ?int
    {
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return $AssetRecord->publisherId;
        }
        return null;
    }
    
    public function yumpuGetLink(): ?string
    {
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return 'yumpu link';
        }
        return null;
    }

    public function yumpuGetEmbedLink(): ?string
    {
        if($AssetRecord = $this->_yumpuGetRecord($this->owner)) {
            return 'yumpu embed link https://www.yumpu.com/de/embed/view/'.$AssetRecord->publisherId;
        }
        return null;
    }
}

?>