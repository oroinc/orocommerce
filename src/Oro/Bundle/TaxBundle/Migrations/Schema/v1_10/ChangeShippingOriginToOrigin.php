<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Changes "shipping_origin" value to "origin" for "oro_tax.use_as_base_by_default" config option.
 */
class ChangeShippingOriginToOrigin implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_config_value SET text_value = :new_value'
            . ' WHERE name = :name AND section = :section AND section = :section',
            [
                'new_value' => 'origin',
                'name' => 'use_as_base_by_default',
                'section' => 'oro_tax',
                'old_value' => 'shipping_origin'
            ]
        ));
    }
}
