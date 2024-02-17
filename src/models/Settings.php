<?php

namespace abmat\publishpdf\models;

use Craft;
use craft\base\Model;

/**
 * craft-issuu settings
 */
class Settings extends Model
{
    public $issuuEnable = false;
    public $issuuClientId = '';
    public $issuuClientSecret = '';
    public $issuuDeleteIfAssetDeleted = false;

    public $yumpuEnable = false;
    public $yumpuApiToken = '';
    public $yumpuDeleteIfAssetDeleted = false;
}
