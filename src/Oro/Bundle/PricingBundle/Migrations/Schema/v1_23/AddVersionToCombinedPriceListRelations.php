<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddVersionToCombinedPriceListRelations implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addField($schema, 'oro_cmb_price_list_to_cus');
        $this->addField($schema, 'oro_cmb_plist_to_cus_gr');
        $this->addField($schema, 'oro_cmb_price_list_to_ws');
    }

    private function addField(Schema $schema, string $tableName): void
    {
        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('version')) {
            $table->addColumn('version', 'integer', ['notnull' => false]);
        }
    }
}
