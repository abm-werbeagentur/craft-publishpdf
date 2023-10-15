<?php

namespace imhomedia\publishpdf\elements\actions;

use craft\base\ElementAction;
use craft\elements\Asset;
use Craft\elements\db\ElementQueryInterface;

class IssuuUploadAction extends ElementAction
{
    public static function displayName(): string
    {
        return 'Upload to Issuu';
    }

    public function getMessage(): ?string
    {
        return 'Asset(s) uploaded to Issuu';
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
        \imhomedia\publishpdf\Plugin::getInstance()->issuu->uploadAsset($asset);
    }
}