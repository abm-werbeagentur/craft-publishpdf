<?php
/**
 * @link https://abm.at
 * @copyright Copyright (c) abm Feregyhazy & Simon GmbH
*/

namespace abmat\publishpdf\migrations;

use craft\db\Migration;
use craft\db\Table;

use abmat\publishpdf\records\AssetRecord;

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
            'publisherId' => $this->string(50),
            'publisherHandle' => $this->string(50),
            'publisherState' => $this->string(50),
            'publisherResponse' => $this->text(),
            'publisherUrl' => $this->text(),
            'publisherEmbedUrl' => $this->text(),
            'publisherEmbedCode' => $this->text(),
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
