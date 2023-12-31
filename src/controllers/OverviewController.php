<?php
/**
 * @link https://abm.at
 * @copyright Copyright (c) abm Feregyhazy & Simon GmbH
*/

namespace abmat\publishpdf\controllers;

use craft\web\Controller;


class OverviewController extends Controller {
	
	public function actionIndex ()
	{
		return $this->renderTemplate('abmat-publishpdf/_overview/index', []);
	}
}