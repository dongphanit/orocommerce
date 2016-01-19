<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPriceListCollection implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOrob2BPriceListCollChangedTable($schema);
        $this->addOrob2BPriceListCollChangedForeignKeys($schema);
    }

    /**
     * Create orob2b_price_list_coll_changed table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListCollChangedTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_coll_changed');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addIndex(['website_id'], 'idx_2e1b092718f45c82', []);
        $table->addIndex(['account_group_id'], 'idx_2e1b0927869a3bf1', []);
        $table->addIndex(['account_id'], 'idx_2e1b09279b6b5fba', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_price_list_coll_changed foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListCollChangedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_coll_changed');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
