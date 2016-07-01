<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class OroB2BPaymentBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->removeFromConfig($queries, 'paypal_payments_pro_validate_cvv');
        $this->removeFromConfig($queries, 'payflow_gateway_validate_cvv');

        $this->addIndexToPaymentTransactionTable($schema);
    }

    /**
     * @param QueryBag $queries
     * @param string $name
     */
    protected function removeFromConfig(QueryBag $queries, $name)
    {
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_config_value WHERE name = :name AND section = :section',
            ['name' => $name, 'section' => OroB2BPaymentExtension::ALIAS]
        ));
    }

    /**
     * @param Schema $schema
     */
    protected function addIndexToPaymentTransactionTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->addUniqueIndex(['access_identifier', 'access_token'], 'orob2b_pay_trans_access_uidx');
    }
}
