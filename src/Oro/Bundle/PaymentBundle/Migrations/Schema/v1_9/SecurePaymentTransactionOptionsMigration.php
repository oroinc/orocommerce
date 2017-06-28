<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SecurePaymentTransactionOptionsMigration implements
    Migration,
    DatabasePlatformAwareInterface,
    OrderedMigrationInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 200;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schemaWithNewColumn = clone $schema;
        $schemaWithNewColumn
            ->getTable('oro_payment_transaction')
            ->addColumn(
                'transaction_options',
                'secure_array',
                ['notnull' => false, 'comment' => '(DC2Type:secure_array)']
            );

        foreach ($this->getSchemaDiff($schema, $schemaWithNewColumn) as $query) {
            $queries->addQuery($query);
        }

        $queries->addQuery(new ConvertPaymentTransactionOptionsToSecureArrayQuery($this->platform));

        $schemaWithModifiedColumn = clone $schemaWithNewColumn;
        $schemaWithModifiedColumn->getTable('oro_payment_transaction')->dropColumn('transaction_options_old');
        foreach ($this->getSchemaDiff($schemaWithNewColumn, $schemaWithModifiedColumn) as $query) {
            $queries->addQuery($query);
        }
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     * @return array
     */
    protected function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();

        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }
}
