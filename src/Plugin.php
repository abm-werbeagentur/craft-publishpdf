<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

/*
Yoda by Blazej Kozlowski & Faux_Pseudo
Credits to https://www.asciiart.eu/movies/star-wars
                    ____
                 _.' :  `._
             .-.'`.  ;   .'`.-.
    __      / : ___\ ;  /___ ; \      __
  ,'_ ""--.:__;".-.";: :".-.":__;.--"" _`,
  :' `.t""--.. '<@.`;_  ',@>` ..--""j.' `;
       `:-.._J '-.-'L__ `-- ' L_..-;'
         "-.__ ;  .-"  "-.  : __.-"
             L ' /.------.\ ' J
              "-.   "--"   .-"
             __.l"-:_JL_;-";.__
          .-j/'.;  ;""""  / .'\"-.
        .' /:`. "-.:     .-" .';  `.
     .-"  / ;  "-. "-..-" .-"  :    "-.
  .+"-.  : :      "-.__.-"      ;-._   \
  ; \  `.; ;                    : : "+. ;
  :  ;   ; ;                    : ;  : \:
 : `."-; ;  ;                  :  ;   ,/;
  ;    -: ;  :                ;  : .-"'  :
  :\     \  : ;             : \.-"      :
   ;`.    \  ; :            ;.'_..--  / ;
   :  "-.  "-:  ;          :/."      .'  :
     \       .-`.\        /t-""  ":-+.   :
      `.  .-"    `l    __/ /`. :  ; ; \  ;
        \   .-" .-"-.-"  .' .'j \  /   ;/
         \ / .-"   /.     .'.' ;_:'    ;
          :-""-.`./-.'     /    `.___.'
                \ `t  ._  /  
                 "-.t-._:'
*/

namespace imhomedia\publishpdf;

use Craft;
use imhomedia\publishpdf\services\Yumpu as YumpuService;
use imhomedia\publishpdf\services\Issuu as IssuuService;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\Asset;
use craft\events\ModelEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use imhomedia\publishpdf\elements\actions\IssuuUploadAction;
use imhomedia\publishpdf\elements\actions\IssuuDeleteAction;
use imhomedia\publishpdf\elements\actions\YumpuUploadAction;
use imhomedia\publishpdf\elements\actions\YumpuDeleteAction;
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

        if (!$this->isInstalled) {
            return;
        }

        // Register Components (Services)
        $this->setComponents([
            'yumpu' => YumpuService::class,
            'issuu' => IssuuService::class,
        ]);

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {

            if (Craft::$app->getRequest()->getIsCpRequest()) {
                if (Craft::$app->getEdition() === Craft::Pro) {
                    $this->_registerPermissions();
                }

                $this->_attachEventHandlers();
                $this->_registerCpRoutes();
                $this->_registerTableAttributes();
                $this->_addAssetActions();
            }
        });
    }

    protected function _addAssetActions(): void
    {
        Event::on(Asset::class, Asset::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
            if($this->getSettings()->issuuEnable) {
                $event->actions[] = IssuuUploadAction::class;
                $event->actions[] = IssuuDeleteAction::class;
            }
            if($this->getSettings()->yumpuEnable) {
                $event->actions[] = YumpuUploadAction::class;
                $event->actions[] = YumpuDeleteAction::class;
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

    private function _attachEventHandlers(): void
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
                'imhomedia-publishpdf-yumpu-upload' => ['label' => Craft::t('app', 'Can upload an asset to Yumpu')],
                'imhomedia-publishpdf-issuu-upload' => ['label' => Craft::t('app', 'Can upload an asset to Issuu')],
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
        
        if($this->settings->issuuEnable) {
            $subNav['imhomedia-publishpdf-issuu'] = [
                'label' => Craft::t('imhomedia-publishpdf', 'Issuu'),
                'url' => 'imhomedia-publishpdf/issuu'
            ];
        }

        if($this->settings->yumpuEnable) {
            $subNav['imhomedia-publishpdf-yumpu'] = [
                'label' => Craft::t('imhomedia-publishpdf', 'Yumpu'),
                'url' => 'imhomedia-publishpdf/yumpu',
            ];
        }

        $subNav['imhomedia-publishpdf-settings'] = ['label' => 'Settings', 'url' => 'settings/plugins/imhomedia-publishpdf'];

		$item['subnav'] = $subNav;

		return $item;
	}

    private function _registerTableAttributes()
    {
        Event::on(Asset::class, Asset::EVENT_REGISTER_TABLE_ATTRIBUTES, function (RegisterElementTableAttributesEvent $event) {
            $event->tableAttributes['yumpu'] = [
                'label' => Craft::t('imhomedia-publishpdf', 'Yumpu'),
            ];
            $event->tableAttributes['issuu'] = [
                'label' => Craft::t('imhomedia-publishpdf', 'Issuu'),
            ];
        });

        Event::on(Asset::class, Asset::EVENT_SET_TABLE_ATTRIBUTE_HTML, function (SetElementTableAttributeHtmlEvent $event) {
            if ($event->attribute === 'yumpu') {
                /** @var Asset $asset */
                $asset = $event->sender;

                $event->html = $this->yumpu->isAssetUploaded($asset);

                // Prevent other event listeners from getting invoked
                $event->handled = true;
            }
            if ($event->attribute === 'issuu') {
                /** @var Asset $asset */
                $asset = $event->sender;

                $event->html = $this->issuu->isAssetUploaded($asset);

                // Prevent other event listeners from getting invoked
                $event->handled = true;
            }
        });
    }
}