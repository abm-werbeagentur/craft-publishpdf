<?php

namespace abmat\publishpdf\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use Craft\elements\db\ElementQueryInterface;

class YumpuUploadAction extends ElementAction
{
    public $message = '';

    public static function displayName(): string
    {
        return Craft::t('abmat-publishpdf', 'Upload to Yumpu');
    }

    public function getMessage(): ?string
    {
        return $this->message;
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
        $return = \abmat\publishpdf\Plugin::getInstance()->yumpu->uploadAsset($asset);
        //Craft::info($return, 'publishpdfdebug');
        if($return === true) {
            $this->message .= 'Asset '.$asset->filename.' upload in progress';
        } else {
            $this->message .= $return;
        }
    }
    
    /**
     * upload only possible for pdf files
     */
    public function getTriggerHtml(): ?string
    {
        // Only enable for duplicatable elements, per canDuplicate()
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        validateSelection: \$selectedItems => {
            for (let i = 0; i < \$selectedItems.length; i++) {
                if(\$selectedItems.eq(i).find('.element').data('kind') != 'pdf') {
                    return false;
                }
            }
            return true;
        },
    });
})();
JS, [static::class]);

        return null;
    }
}