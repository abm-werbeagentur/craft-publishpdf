<?php
/**
 * @link https://abm.at
 * @copyright Copyright (c) abm Feregyhazy & Simon GmbH
*/

namespace abmat\publishpdf\controllers;

use craft\web\Controller;
use abmat\publishpdf\services\Yumpu;

class YumpuController extends Controller {
	
	public function actionIndex ()
	{
        $token = \abmat\publishpdf\Plugin::getInstance()->getSettings()->yumpuApiToken;
        if(!$token) {
            return $this->renderTemplate('abmat-publishpdf/_yumpu/error_token');
        } else {
            $yumpuService = new Yumpu();
            $categories = $yumpuService->getCategories();
            $documents = $yumpuService->getDocuments();
            return $this->renderTemplate('abmat-publishpdf/_yumpu/index', ['categories' => $categories, 'documents' => $documents]);
        }
	}
}