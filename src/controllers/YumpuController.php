<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\controllers;

use craft\web\Controller;
use imhomedia\publishpdf\services\Yumpu;

class YumpuController extends Controller {
	
	public function actionIndex ()
	{
        $token = \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->yumpuApiToken;
        if(!$token) {
            return $this->renderTemplate('imhomedia-publishpdf/_yumpu/error_token');
        } else {
            $yumpuService = new Yumpu();
            $categories = $yumpuService->getCategories();
            $documents = $yumpuService->getDocuments();
            return $this->renderTemplate('imhomedia-publishpdf/_yumpu/index', ['categories' => $categories, 'documents' => $documents]);
        }
	}
}