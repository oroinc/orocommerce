<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables update **/
        $this->alterAddressTable($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterAddressTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_quote_address');
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
    }
}
