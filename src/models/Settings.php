<?php

namespace imhomedia\publishpdf\models;

use Craft;
use craft\base\Model;

/**
 * craft-issuu settings
 */
class Settings extends Model
{
    public $issuuEnable = false;
    public $issuuApiToken = '';
    public $issuuDeleteIfAssetDeleted = false;

    public $yumpuEnable = false; /* https://github.com/Yumpu/Yumpu-SDK */
    public $yumpuApiToken = '';
    public $yumpuDeleteIfAssetDeleted = false;
}
