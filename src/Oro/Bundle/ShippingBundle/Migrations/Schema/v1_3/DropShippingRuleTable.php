<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropShippingRuleTable implements
    Migration,
    OrderedMigrationInterface,
    ActivityExtensionAwareInterface,
    ExtendOptionsManagerAwareInterface
{
    use MigrationConstraintTrait;
    use ActivityExtensionAwareTrait;
    use ExtendOptionsManagerAwareTrait;

    private const NOTE_NO_SHIPPING_RULE_ENTITY_CLASS_COLUMN_NAME = '_ac74095a';
    private const NOTE_NO_SHIPPING_RULE_ENTITY_CLASS_OBJECT_NAME = 'oro_note!_ac74095a';
    private const NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE = 'shipping_rule_7b77295b_id';
    private const NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE = 'shipping_rule_fd89fead_id';

    #[\Override]
    public function getOrder(): int
    {
        return 40;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->removeOptionsFromExtendOptionManager();
        $this->removeNotesAssociation($schema);
        $schema->dropTable('oro_shipping_rule');
    }

    private function removeNotesAssociation(Schema $schema): void
    {
        $notes = $schema->getTable('oro_note');

        if ($notes->hasColumn(self::NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE)) {
            $this->dropForeignKeyAndColumn($schema, 'oro_note', self::NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE);
        }

        if ($notes->hasColumn(self::NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE)) {
            $this->dropForeignKeyAndColumn($schema, 'oro_note', self::NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE);
        }

        $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', 'oro_shipping_rule');
        if ($associationTableName && $schema->hasTable($associationTableName)) {
            $schema->dropTable($associationTableName);
        }
    }

    protected function removeOptionsFromExtendOptionManager(): void
    {
        $options = $this->extendOptionsManager->getExtendOptions();
        $this->extendOptionsManager->removeTableOptions('oro_shipping_rule');
        if (isset($options[self::NOTE_NO_SHIPPING_RULE_ENTITY_CLASS_OBJECT_NAME])) {
            $this->extendOptionsManager->removeColumnOptions(
                'oro_note',
                self::NOTE_NO_SHIPPING_RULE_ENTITY_CLASS_COLUMN_NAME
            );
        }
    }

    private function dropForeignKeyAndColumn(Schema $schema, string $tableName, string $relationColumn): void
    {
        $table = $schema->getTable($tableName);
        $foreignKey = $this->getConstraintName($table, $relationColumn);
        $table->removeForeignKey($foreignKey);
        $table->dropColumn($relationColumn);
    }
}
