<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCustomerTaxCodeOrganizationUniqueIndex implements Migration
{
    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addCustomerTaxCodeOrganizationUniqueIndex($schema);
    }

    /**
     * @throws \Doctrine\DBAL\Sgit chema\SchemaException
     */
    private function addCustomerTaxCodeOrganizationUniqueIndex(Schema $schema): void
    {
        $table = $schema->getTable('oro_tax_customer_tax_code');
        $table->dropIndex('UNIQ_E98BB26B77153098');
        $table->addUniqueIndex(['code','organization_id'], 'oro_customer_tax_code_organization_unique_index');
    }
}
