<?php
/**
 * @link https://abm.at
 * @copyright Copyright (c) abm Feregyhazy & Simon GmbH
*/

namespace abmat\publishpdf\controllers;

use craft\web\Controller;
use abmat\publishpdf\services\Issuu;

class IssuuController extends Controller {
	
	public function actionIndex ()
	{
		$issuuClientSecret = \abmat\publishpdf\Plugin::getInstance()->getSettings()->issuuClientSecret;
        if(!$issuuClientSecret) {
            return $this->renderTemplate('abm-publishpdf/_issuu/error_token');
        } else {
            $issuuService = new Issuu();
            $RET = $issuuService->getDocuments();
            if($RET['error'] === true) {
                return $this->renderTemplate('abm-publishpdf/_issuu/error', ['msg' => $RET['msg']]);
            } else {
                return $this->renderTemplate('abm-publishpdf/_issuu/index', ['documents' => $RET['documents']]);
            }
        }
	}
}