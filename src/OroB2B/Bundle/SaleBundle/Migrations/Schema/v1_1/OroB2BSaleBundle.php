<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;

class OroB2BSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        $table = $schema->getTable('orob2b_sale_quote_product');
        $table->addColumn('free_form_product', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('free_form_product_replacement', 'string', ['notnull' => false, 'length' => 255]);
        $table->changeColumn('product_sku', ['notnull' => false]);

        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'OroB2B\Bundle\SaleBundle\Entity\Quote',
                'grouping',
                'groups',
                null
            )
        );
    }
}
