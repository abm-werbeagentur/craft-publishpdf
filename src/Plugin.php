<?php
/**
 * @link https://abm.at
 * @copyright Copyright (c) abm Feregyhazy & Simon GmbH
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

namespace abmat\publishpdf;

use Craft;
use abmat\publishpdf\services\Yumpu as YumpuService;
use abmat\publishpdf\services\Issuu as IssuuService;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\Asset;
use craft\events\DefineBehaviorsEvent;
use craft\events\ModelEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\events\ReplaceAssetEvent;
use craft\services\Assets;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use abmat\publishpdf\behaviors\AssetBehavior;
use abmat\publishpdf\elements\actions\IssuuUploadAction;
use abmat\publishpdf\elements\actions\IssuuDeleteAction;
use abmat\publishpdf\elements\actions\YumpuUploadAction;
use abmat\publishpdf\elements\actions\YumpuDeleteAction;
use yii\base\Event;

use abmat\publishpdf\models\Settings;


/**
 * craft-publishpdf plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @author abm <office@abm.at>
 * @copyright abm Feregyhazy & Simon GmbH
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

                $this->_registerCpRoutes();
                $this->_attachEventHandlers();
                $this->_registerTableAttributes();
                $this->_addAssetActions();
            }

            $this->_attachBehaviors();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('abmat-publishpdf/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function _attachEventHandlers(): void
    {
        Event::on(
            Asset::class,
            Asset::EVENT_BEFORE_DELETE,
            static function (Event $event) {
                $asset = $event->sender;
                
                if($asset->hardDelete) {
                    //remove from publisher if file is hard deleted
                    $thisPlugin = \abmat\publishpdf\Plugin::getInstance();

                    /* remove from issuu if setting is true and asset is uploaded to isusu */
                    if($thisPlugin->getSettings()->issuuDeleteIfAssetDeleted && $thisPlugin->issuu->isUploaded($asset)) {
                        $thisPlugin->issuu->deleteAsset($asset);
                    }
                    
                    /* remove from yumpu if setting is true and asset is uploaded to isusu */
                    if($thisPlugin->getSettings()->yumpuDeleteIfAssetDeleted && $thisPlugin->yumpu->isUploaded($asset)) {
                        $thisPlugin->yumpu->deleteAsset($asset);
                    }
                }
            }
        );
        Event::on(
            Assets::class,
            Assets::EVENT_AFTER_REPLACE_ASSET,
            static function (ReplaceAssetEvent $event) {
                $asset = $event->asset;
                $thisPlugin = \abmat\publishpdf\Plugin::getInstance();

                if($thisPlugin->issuu->isUploaded($asset)) {
                    //asset is uploaded to issuu and should get replaced
                    $thisPlugin->issuu->replaceAsset($asset);
                }

                if($thisPlugin->yumpu->isUploaded($asset)) {
                    //asset is uploaded to yumpu and should get replaced
                    $thisPlugin->yumpu->replaceAsset($asset);
                }
            }
        );
    }

    private function _registerTableAttributes()
    {
        Event::on(Asset::class, Asset::EVENT_REGISTER_TABLE_ATTRIBUTES, function (RegisterElementTableAttributesEvent $event) {
            $event->tableAttributes['yumpu'] = [
                'label' => Craft::t('abmat-publishpdf', 'Yumpu'),
            ];
            $event->tableAttributes['issuu'] = [
                'label' => Craft::t('abmat-publishpdf', 'Issuu'),
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

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event): void {

            $permissions = [
                'abmat-publishpdf-settings' => ['label' => Craft::t('app', 'Settings')],
                'abmat-publishpdf-yumpu-upload' => ['label' => Craft::t('app', 'Can upload an asset to Yumpu')],
                'abmat-publishpdf-issuu-upload' => ['label' => Craft::t('app', 'Can upload an asset to Issuu')],
            ];

            $event->permissions[] = [
                'heading' => Craft::t('abmat-publishpdf', 'Publish PDF'),
                'permissions' => $permissions,
            ];
        });
    }

    private function _registerCpRoutes (): void
	{
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event): void {
            
            $event->rules['abmat-publishpdf'] = 'abmat-publishpdf/overview/index';
            $event->rules['abmat-publishpdf/yumpu'] = 'abmat-publishpdf/yumpu/index';
            $event->rules['abmat-publishpdf/issuu'] = 'abmat-publishpdf/issuu/index';
        });
	}

    private function _attachBehaviors(): void
    {
        Event::on(
            Asset::class,
            Asset::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $e) {
                $e->behaviors['publishpdf'] = AssetBehavior::class;
            }
        );
    }

    public function getCpNavItem (): ?array
	{
		$item = parent::getCpNavItem();
		$currentUser = Craft::$app->user;

		// $subNav = [
		// 	'abmat-publishpdf-dashboard' => ['label' => 'Dashboard', 'url' => 'abmat-publishpdf'],
		// ];
        
        if($this->settings->issuuEnable) {
            $subNav['abmat-publishpdf-issuu'] = [
                'label' => Craft::t('abmat-publishpdf', 'Issuu'),
                'url' => 'abmat-publishpdf/issuu'
            ];
        }

        if($this->settings->yumpuEnable) {
            $subNav['abmat-publishpdf-yumpu'] = [
                'label' => Craft::t('abmat-publishpdf', 'Yumpu'),
                'url' => 'abmat-publishpdf/yumpu',
            ];
        }

        $subNav['abmat-publishpdf-settings'] = ['label' => 'Settings', 'url' => 'settings/plugins/abmat-publishpdf'];

		$item['subnav'] = $subNav;

		return $item;
	}
}