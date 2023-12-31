<?php

namespace abmat\publishpdf\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use Craft\elements\db\ElementQueryInterface;

class YumpuDeleteAction extends ElementAction
{
    public string $message = '';

    public static function displayName(): string
    {
        return Craft::t('abmat-publishpdf', 'Delete from Yumpu');
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getConfirmationMessage(): ?string
    {
        return Craft::t('abmat-publishpdf', 'Really delete from Yumpu?');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        //execute directly ... no queue here
        collect($query->all())
            ->map(fn($asset) => $this->delete($asset));

        return true;
    }

    function delete(Asset $asset): void
    {
        $return = \abmat\publishpdf\Plugin::getInstance()->yumpu->deleteAsset($asset);
        if($return === true) {
            $this->message .= 'Asset '.$asset->filename.' deleted from Yumpu';
        } else {
            $this->message .= $return;
        }
    }

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