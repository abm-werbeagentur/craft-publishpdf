<?php

namespace imhomedia\publishpdf;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\Asset;
use craft\events\ModelEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\Cp;

use yii\base\Event;

use imhomedia\publishpdf\models\Settings;


/**
 * craft-publishpdf plugin
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
    public bool $hasCpSection  = true;
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

            if (Craft::$app->getRequest()->getIsCpRequest()) {

                if (Craft::$app->getEdition() === Craft::Pro) {
                    $this->_registerPermissions();
                }
                $this->_registerCpRoutes();
            }
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('imhomedia-publishpdf/_settings.twig', [
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
                    Craft::info($asset->volume->handle, 'publishpdfdebug');
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
                    Craft::info("delete " . $asset, 'publishpdfdebug');
                }
            }
        );
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event): void {

            $permissions = [
                'imhomedia-publishpdf-settings' => ['label' => Craft::t('app', 'Settings')],
            ];

            $event->permissions[] = [
                'heading' => Craft::t('imhomedia-publishpdf', 'Publish PDF'),
                'permissions' => $permissions,
            ];
        });
    }

    private function _registerCpRoutes (): void
	{
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event): void {
            
            $event->rules['imhomedia-publishpdf'] = 'imhomedia-publishpdf/overview/index';
            $event->rules['imhomedia-publishpdf/yumpu'] = 'imhomedia-publishpdf/yumpu/index';
            $event->rules['imhomedia-publishpdf/issuu'] = 'imhomedia-publishpdf/issuu/index';
        });
	}

    public function getCpNavItem (): ?array
	{
		$item = parent::getCpNavItem();
		$currentUser = Craft::$app->user;

		$subNav = [
			'imhomedia-publishpdf-dashboard' => ['label' => 'Dashboard', 'url' => 'imhomedia-publishpdf'],
		];

        if($this->settings->yumpuEnable) {
            $subNav['imhomedia-publishpdf-yumpu'] = [
                'label' => Craft::t('imhomedia-publishpdf', 'Yumpu'),
                'url' => 'imhomedia-publishpdf/yumpu',
            ];
        }
        
        if($this->settings->issuuEnable) {
            $subNav['imhomedia-publishpdf-issuu'] = [
                'label' => Craft::t('imhomedia-publishpdf', 'Issuu'),
                'url' => 'imhomedia-publishpdf/issuu'
            ];
        }

        $subNav['imhomedia-publishpdf-settings'] = ['label' => 'Settings', 'url' => 'settings/plugins/imhomedia-publishpdf'];

		$item['subnav'] = $subNav;

		return $item;
	}
}