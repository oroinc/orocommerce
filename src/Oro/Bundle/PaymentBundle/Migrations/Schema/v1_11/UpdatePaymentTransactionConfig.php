<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldModeQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Change PaymentTransaction mode to default and hide some sensitive fields.
 */
class UpdatePaymentTransactionConfig implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $updateEntityModeSql = <<<EOF
UPDATE oro_entity_config SET mode = :mode
WHERE class_name = :class_name
EOF;

        $updateQuery = new ParametrizedSqlMigrationQuery();
        $updateQuery->addSql(
            $updateEntityModeSql,
            ['class_name' => 'Oro\\Bundle\\PaymentBundle\\Entity\\PaymentTransaction', 'mode' => 'default'],
            ['class_name' => Types::STRING, 'mode' => Types::STRING]
        );
        $queries->addPreQuery($updateQuery);

        foreach (['accessToken', 'reference', 'request', 'response', 'transactionOptions'] as $fieldName) {
            $updateModeQuery = new UpdateEntityConfigFieldModeQuery(
                'Oro\Bundle\PaymentBundle\Entity\PaymentTransaction',
                $fieldName,
                'hidden'
            );
            $queries->addPostQuery($updateModeQuery);

            $auditQuery = new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\PaymentBundle\Entity\PaymentTransaction',
                $fieldName,
                'dataaudit',
                'auditable',
                false
            );
            $queries->addPostQuery($auditQuery);

            $importExportQuery = new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\PaymentBundle\Entity\PaymentTransaction',
                $fieldName,
                'importexport',
                'excluded',
                true
            );
            $queries->addPostQuery($importExportQuery);
        }
    }
}
