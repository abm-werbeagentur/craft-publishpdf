<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\migrations;

use craft\db\Migration;
use craft\db\Table;

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

        $this->addForeignKey(null,
            AssetRecord::tableName(),
            'assetId',
            Table::ASSETS,
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists(AssetRecord::$tableName);
    }
}
