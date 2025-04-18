<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Set default value to false for oro_sale_quote_prod_offer.allow_increments
 */
class SetDefaultValueForAllowIncrements implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->getTable('oro_sale_quote_prod_offer')
            ->getColumn('allow_increments')
            ->setDefault(false);
    }
}
