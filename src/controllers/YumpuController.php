<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\controllers;

use craft\web\Controller;


class YumpuController extends Controller {
	
	public function actionIndex ()
	{
		return $this->renderTemplate('imhomedia-publishpdf/_yumpu/index', []);
	}
}