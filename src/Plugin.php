<?php

namespace imhomedia\issuu;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\Asset;
use craft\events\ModelEvent;
use yii\base\Event;

use imhomedia\issuu\models\Settings;


/**
 * craft-issuu plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @author Imhomedia <craft@imhomedia.at>
 * @copyright Imhomedia
 * @license https://craftcms.github.io/license/ Craft License
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('craft-issuu/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            Asset::class,
            Asset::EVENT_AFTER_PROPAGATE,
            static function (ModelEvent $event) {
                $asset = $event->sender;

                if($asset->extension == 'pdf' && !$asset->getIsDraft()) {
                    Craft::info($asset->volume->handle, 'issuudebug');
                }
            }
        );

        Event::on(
            Asset::class,
            Asset::EVENT_AFTER_DELETE,
            static function (Event $event) {
                $asset = $event->sender;
                if($asset->hardDelete) {
                    //remove from issuu if file is hard deleted
                    Craft::info("delete " . $asset, 'issuudebug');
                }
            }
        );
    }
}
