<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_26;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ConfigureRequestGrids implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $options = new OroOptions();
        $options->set('grid', 'default', 'rfp-requests-grid');
        $options->set('grid', 'context', 'rfp-requests-for-context-grid');
        $schema->getTable('oro_rfp_request')->addOption(OroOptions::KEY, $options);
    }
}
