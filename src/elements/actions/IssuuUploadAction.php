<?php

namespace abmat\publishpdf\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use Craft\elements\db\ElementQueryInterface;

class IssuuUploadAction extends ElementAction
{
    public $message = '';
    public $uploadReturn = true;

    public static function displayName(): string
    {
        return Craft::t('abmat-publishpdf', 'Upload to Issuu');
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

        return $this->uploadReturn;
    }

    function upload(Asset $asset): void
    {
        $return = \abmat\publishpdf\Plugin::getInstance()->issuu->uploadAsset($asset);

        if($return === true) {
            $this->message .= 'Asset '.$asset->filename.' upload in progress';
        } else {
            $this->message .= $return;
            $this->uploadReturn = false;
        }
    }

    /**
     * upload only possible for pdf and word files
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        validateSelection: \$selectedItems => {
            for (let i = 0; i < \$selectedItems.length; i++) {
                if(\$selectedItems.eq(i).find('.element').data('kind') != 'pdf' && \$selectedItems.eq(i).find('.element').data('kind') != 'word') {
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