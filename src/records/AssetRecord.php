<?php
/**
 * @link https://abm.at
 * @copyright Copyright (c) abm Feregyhazy & Simon GmbH
*/

namespace abmat\publishpdf\records;

use craft\db\ActiveRecord;

/**
 * Class EntryRecord
 *
 * @package abmat\checkit\records
 */
class AssetRecord extends ActiveRecord
{
	public static $tableName = '{{%abmat_publishpdf_asset_meta}}';

	public static function tableName ()
	{
		return self::$tableName;
	}
}