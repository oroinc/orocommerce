<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DropShippingRuleTable implements
    Migration,
    OrderedMigrationInterface,
    ActivityExtensionAwareInterface,
    ContainerAwareInterface
{
    use MigrationConstraintTrait;

    const SHIPPING_RULE_CLASS_NAME = 'Oro\Bundle\ShippingBundle\Entity\ShippingRule';
    const SHIPPING_RULE_TABLE = 'oro_shipping_rule';
    const NOTE_TABLE = 'oro_note';
    const NOTE_NO_SHIPPING_RULE_ENTITY_CLASS_COLUMN_NAME = '_ac74095a';
    const NOTE_NO_SHIPPING_RULE_ENTITY_CLASS_OBJECT_NAME = 'oro_note!_ac74095a';
    const NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE = 'shipping_rule_7b77295b_id';
    const NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE = 'shipping_rule_fd89fead_id';

    /**
     * @var ActivityExtension
     */
    private $activityExtension;

    /**
     * @var ContainerInterface
     */
    private $container;

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
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * @inheritDoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->removeOptionsFromExtendOptionManager();
        $this->removeNotesAssociation($schema);
        $schema->dropTable(self::SHIPPING_RULE_TABLE);
    }

    private function removeNotesAssociation(Schema $schema)
    {
        $notes = $schema->getTable(self::NOTE_TABLE);

        if ($notes->hasColumn(self::NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE)) {
            $this->dropForeignKeyAndColumn($schema, self::NOTE_TABLE, self::NOTE_ASSOCIATION_COLUMN_BEFORE_UPDATE);
        }

        if ($notes->hasColumn(self::NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE)) {
            $this->dropForeignKeyAndColumn($schema, self::NOTE_TABLE, self::NOTE_ASSOCIATION_COLUMN_AFTER_UPDATE);
        }

        $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', 'oro_shipping_rule');
        if ($associationTableName && $schema->hasTable($associationTableName)) {
            $schema->dropTable($associationTableName);
        }
    }

    protected function removeOptionsFromExtendOptionManager()
    {
        $optionManager = $this->container->get('oro_entity_extend.migration.options_manager');
        $options = $optionManager->getExtendOptions();
        $optionManager->removeTableOptions(self::SHIPPING_RULE_TABLE);
        if (isset($options[self::NOTE_NO_SHIPPING_RULE_ENTITY_CLASS_OBJECT_NAME])) {
            $optionManager->removeColumnOptions(self::NOTE_TABLE, self::NOTE_NO_SHIPPING_RULE_ENTITY_CLASS_COLUMN_NAME);
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
