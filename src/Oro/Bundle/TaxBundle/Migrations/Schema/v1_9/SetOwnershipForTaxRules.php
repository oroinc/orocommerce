<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class SetOwnershipForTaxRules implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_tax_rule');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );

        $options = new OroOptions();
        $options->set('ownership', 'owner_type', 'ORGANIZATION');
        $options->set('ownership', 'owner_field_name', 'organization');
        $options->set('ownership', 'owner_column_name', 'organization_id');
        $table->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery(new SqlMigrationQuery(
            'UPDATE oro_tax_rule SET organization_id = ('
            . 'SELECT organization_id FROM oro_tax_product_tax_code'
            . ' WHERE oro_tax_product_tax_code.id = oro_tax_rule.product_tax_code_id)'
        ));
    }
}
