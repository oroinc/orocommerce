<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOrganizationToProductTaxCode implements Migration
{
    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addOrganizationToProductTaxCode($schema);
        $this->addProductTaxCodeOrganizationUniqueIndex($schema);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function addOrganizationToProductTaxCode(Schema $schema): void
    {
        $table = $schema->getTable('oro_tax_product_tax_code');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function addProductTaxCodeOrganizationUniqueIndex(Schema $schema): void
    {
        $table = $schema->getTable('oro_tax_product_tax_code');
        $table->dropIndex('UNIQ_5AF53A4A77153098');
        $table->addUniqueIndex(['code','organization_id'], 'oro_product_tax_code_organization_unique_index');
    }
}
