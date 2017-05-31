<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements Migration
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new OroPriceListStrategyQuery());
    }
}
