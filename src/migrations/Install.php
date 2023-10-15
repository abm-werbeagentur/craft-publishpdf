<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\migrations;

use craft\db\Migration;
use craft\helpers\Db;
use craft\helpers\MigrationHelper;

use imhomedia\publishpdf\records\AssetRecord;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTables();

		return true;
    }

    public function createTables(): void
    {
       $this->createTable(AssetRecord::$tableName, [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer(),
            'publisherHandle' => $this->string(50),
            'publisherResponse' => $this->json(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists(AssetRecord::$tableName);
    }
}
