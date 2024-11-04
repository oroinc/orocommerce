<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ConfigureOrderGrids implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $options = new OroOptions();
        $options->set('grid', 'default', 'orders-grid');
        $options->set('grid', 'context', 'orders-for-context-grid');
        $schema->getTable('oro_order')->addOption(OroOptions::KEY, $options);
    }
}
