<?php

namespace imhomedia\publishpdf\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use Craft\elements\db\ElementQueryInterface;

class IssuuDeleteAction extends ElementAction
{
    public string $message = '';

    public static function displayName(): string
    {
        return Craft::t('imhomedia-publishpdf', 'Delete from Issuu');
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getConfirmationMessage(): ?string
    {
        return Craft::t('imhomedia-publishpdf', 'Really delete from Issuu?');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        //check progress for recently uploaded documents:
        \imhomedia\publishpdf\Plugin::getInstance()->issuu->checkProgress();

        //execute directly ... no queue here
        collect($query->all())
            ->map(fn($asset) => $this->delete($asset));

        return true;
    }

    function delete(Asset $asset): void
    {
        //delete asset
        $return = \imhomedia\publishpdf\Plugin::getInstance()->issuu->deleteAsset($asset);
        if($return === true) {
            $this->message .= 'Asset '.$asset->filename.' deleted from Issuu';
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