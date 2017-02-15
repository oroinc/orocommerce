<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOrganizationOwnershipToPaymentMethodsConfigsRule implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_payment_mtds_cfgs_rl');

        $table->addColumn('organization_id', 'integer', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $queries->addPostQuery(
            new SetDefaultPaymentMethodsConfigsRuleOrganizationQuery()
        );
    }
}
