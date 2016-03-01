<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createInexForFieldShippingAddress($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createInexForFieldShippingAddress(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addUniqueIndex(['shipping_address_id'], 'UNIQ_4F66B6F64D4CFF2B');
    }
}
