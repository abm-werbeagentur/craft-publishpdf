<?php

namespace imhomedia\publishpdf\elements\actions;

use craft\base\ElementAction;
use craft\elements\Asset;
use Craft\elements\db\ElementQueryInterface;

class YumpuDeleteAction extends ElementAction
{
    public static function displayName(): string
    {
        return 'Delete from Yumpu';
    }

    public function getMessage(): ?string
    {
        return 'Asset(s) deleted from Yumpu';
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
        \imhomedia\publishpdf\Plugin::getInstance()->yumpu->deleteAsset($asset);
    }
}