<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOrganizationToPriceAttribute implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addOrganizationToPriceAttributePriceListTable($schema);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function addOrganizationToPriceAttributePriceListTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_attribute_pl');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
