<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlSchemaUpdateMigrationQuery;

class RenameTablesSequences implements Migration, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameSequence($schema, $queries, 'oro_shipping_rule_mthd_config', 'oro_ship_method_config');
        $this->renameSequence($schema, $queries, 'oro_shipping_rule_mthd_tp_cnfg', 'oro_ship_method_type_config');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $newTableName
     * @param string $oldTableName
     */
    private function renameSequence(Schema $schema, QueryBag $queries, $oldTableName, $newTableName)
    {
        if ($this->platform->supportsSequences()) {
            $primaryKey = $schema->getTable($newTableName)->getPrimaryKeyColumns();
            if (count($primaryKey) === 1) {
                $primaryKey = reset($primaryKey);
                $oldSequenceName = $this->platform->getIdentitySequenceName($oldTableName, $primaryKey);
                if ($schema->hasSequence($oldSequenceName)) {
                    $newSequenceName = $this->platform->getIdentitySequenceName($newTableName, $primaryKey);
                    if ($this->platform instanceof PostgreSqlPlatform) {
                        $renameSequenceQuery = new SqlSchemaUpdateMigrationQuery(
                            "ALTER SEQUENCE $oldSequenceName RENAME TO $newSequenceName"
                        );
                        $queries->addQuery($renameSequenceQuery);
                    }
                }
            }
        }
    }
}
