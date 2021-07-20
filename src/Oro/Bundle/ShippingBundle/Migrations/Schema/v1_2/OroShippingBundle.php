<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroShippingBundle implements Migration, OrderedMigrationInterface
{
    public function getOrder()
    {
        return 2;
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // fill createdAt and updatedAt
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_shipping_rule SET created_at = :date, updated_at = :date',
                ['date' => new \DateTime('now', new \DateTimeZone('UTC'))],
                ['date' => Types::DATETIME_MUTABLE]
            )
        );

        $table = $schema->getTable('oro_shipping_rule');
        $table->getColumn('created_at')->setOptions(['notnull' => true]);
        $table->getColumn('updated_at')->setOptions(['notnull' => true]);
    }
}
