<?php

namespace imhomedia\publishpdf\elements\actions;

use craft\base\ElementAction;
use craft\elements\Asset;
use Craft\elements\db\ElementQueryInterface;

class YumpuUploadAction extends ElementAction
{
    public static function displayName(): string
    {
        return 'Upload to Yumpu';
    }

    public function getMessage(): ?string
    {
        return 'Asset(s) uploaded to Yumpu';
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        //execute directly ... no queue here
        collect($query->all())
            ->map(fn($asset) => $this->upload($asset));

        return true;
    }

    function upload(Asset $asset): void
    {
        \imhomedia\publishpdf\Plugin::getInstance()->yumpu->uploadAsset($asset);
    }
}