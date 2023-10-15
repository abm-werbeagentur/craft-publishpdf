<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\controllers;

use craft\web\Controller;


class IssuuController extends Controller {
	
	public function actionIndex ()
	{
		return $this->renderTemplate('imhomedia-publishpdf/_issuu/index', []);
	}
}