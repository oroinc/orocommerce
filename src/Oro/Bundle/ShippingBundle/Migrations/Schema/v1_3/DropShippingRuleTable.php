<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropShippingRuleTable implements Migration, OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    const SHIPPING_RULE_CLASS_NAME = 'Oro\Bundle\ShippingBundle\Entity\ShippingRule';
    const NOTE_TABLE = 'oro_note';
    const NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE = 'shipping_rule_7b77295b_id';
    const NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE = 'shipping_rule_fd89fead_id';

    /**
     * @return int
     */
    public function getOrder()
    {
        return 40;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('oro_shipping_rule');
        $this->removeNotesAssociation($schema);
    }

    /**
     * @param Schema $schema
     */
    private function removeNotesAssociation(Schema $schema)
    {
        $notes = $schema->getTable(self::NOTE_TABLE);

        if ($notes->hasColumn(self::NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE)) {
            $this->dropForeignKeyAndColumn($schema, self::NOTE_TABLE, self::NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE);
        }

        if ($notes->hasColumn(self::NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE)) {
            $this->dropForeignKeyAndColumn($schema, self::NOTE_TABLE, self::NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE);
        }
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param string $relationColumn
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function dropForeignKeyAndColumn(Schema $schema, $tableName, $relationColumn)
    {
        $table = $schema->getTable($tableName);
        $foreignKey = $this->getConstraintName($table, $relationColumn);
        $table->removeForeignKey($foreignKey);
        $table->dropColumn($relationColumn);
    }
}
