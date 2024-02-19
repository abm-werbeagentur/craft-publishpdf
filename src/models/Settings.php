<?php

namespace abmat\publishpdf\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

/**
 * craft-issuu settings
 */
class Settings extends Model
{
    /**
	 * @var bool
	 */
    public $issuuEnable = false;

    /**
	 * @var string
	 */
    public $issuuClientId = '';

    /**
	 * @var string
	 */
    public $issuuClientSecret = '';

    /**
	 * @var bool
	 */
    public $issuuDeleteIfAssetDeleted = false;

    /**
	 * @var bool
	 */
    public $yumpuEnable = false;

    /**
	 * @var string
	 */
    public $yumpuApiToken = '';

    /**
	 * @var bool
	 */
    public $yumpuDeleteIfAssetDeleted = false;


    /**
	 * @return string
	 */
	public function getIssuuClientId(): string
	{
		return App::parseEnv($this->issuuClientId);
	}
    /**
	 * @return string
	 */
	public function getIssuuClientSecret(): string
	{
		return App::parseEnv($this->issuuClientSecret);
	}
    /**
	 * @return string
	 */
	public function getYumpuApiToken(): string
	{
		return App::parseEnv($this->yumpuApiToken);
	}
}
