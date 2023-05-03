<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\SecurityBundle\Migrations\Schema\SetOwnershipTypeQuery;

/**
 * Adds organization column to price list entity.
 */
class AddOrganizationToPrice implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_price_list');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );

        $queries->addQuery(
            new SetOwnershipTypeQuery(
                PriceList::class,
                [
                    'owner_type' => 'ORGANIZATION',
                    'owner_field_name' => 'organization',
                    'owner_column_name' => 'organization_id'
                ]
            )
        );
    }
}
