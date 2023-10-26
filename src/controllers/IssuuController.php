<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\controllers;

use craft\web\Controller;
use imhomedia\publishpdf\services\Issuu;

class IssuuController extends Controller {
	
	public function actionIndex ()
	{
		$issuuSecret = \imhomedia\publishpdf\Plugin::getInstance()->getSettings()->issuuSecret;
        if(!$issuuSecret) {
            return $this->renderTemplate('imhomedia-publishpdf/_issuu/error_token');
        } else {
            $issuuService = new Issuu();
            $RET = $issuuService->getDocuments();
            if($RET['error'] === true) {
                return $this->renderTemplate('imhomedia-publishpdf/_issuu/error', ['msg' => $RET['msg']]);
            } else {
                return $this->renderTemplate('imhomedia-publishpdf/_issuu/index', ['documents' => $RET['documents']]);
            }
        }
	}
}