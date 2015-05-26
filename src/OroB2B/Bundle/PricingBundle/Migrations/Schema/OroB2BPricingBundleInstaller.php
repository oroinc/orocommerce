<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_0\OroB2BPricingBundle as OroB2BPricingBundle10;

class OroB2BPricingBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $migration = new OroB2BPricingBundle10();
        $migration->up($schema, $queries);
    }
}
