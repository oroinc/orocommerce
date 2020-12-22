<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add fedex_ignore_package_dimension field to oro_integration_transport table
 */
class AddIgnoreDimensionsField implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_transport');

        if (!$table->hasColumn('fedex_ignore_package_dimension')) {
            $table->addColumn('fedex_ignore_package_dimension', 'boolean', ['notnull' => false, 'default' => false]);
        }
    }
}
