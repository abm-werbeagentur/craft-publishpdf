<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\records;

use craft\db\ActiveRecord;

/**
 * Class EntryRecord
 *
 * @package abmat\checkit\records
 */
class AssetRecord extends ActiveRecord
{
	public static $tableName = '{{%imhomedia_publishpdf_asset_meta}}';

	public static function tableName ()
	{
		return self::$tableName;
	}
}