<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTaxBundleStage2 implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('oro_tax_cus_tax_code_cus');
        $schema->dropTable('oro_tax_cus_grp_tc_cus_grp');
        $schema->dropTable('oro_tax_prod_tax_code_prod');
    }

    #[\Override]
    public function getOrder()
    {
        return 2;
    }
}
