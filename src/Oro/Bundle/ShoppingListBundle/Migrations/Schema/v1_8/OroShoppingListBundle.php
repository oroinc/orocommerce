<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroShoppingListBundle implements
    Migration,
    ExtendExtensionAwareInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addShoppingListCustomerVisitorInverseRelation($schema, $queries);
    }

    private function addShoppingListCustomerVisitorInverseRelation(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_customer_visitor');
        $targetTable = $schema->getTable('oro_shopping_list');

        // Column names are used to show a title of target entity
        $tableTitleColumnNames = $table->getPrimaryKeyColumns();
        // Column names are used to show detailed info about target entity
        $tableDetailedColumnNames = $table->getPrimaryKeyColumns();
        // Column names are used to show target entity in a grid
        $tableGridColumnNames = $table->getPrimaryKeyColumns();

        $this->extendExtension->addManyToManyInverseRelation(
            $schema,
            $table,
            'shoppingLists',
            $targetTable,
            'visitors',
            $tableTitleColumnNames,
            $tableDetailedColumnNames,
            $tableGridColumnNames,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false],
                'dataaudit' => ['auditable' => false]
            ]
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                CustomerVisitor::class,
                'extend',
                'relation',
                []
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                CustomerVisitor::class,
                'shoppingLists',
                'extend',
                'state',
                ExtendScope::STATE_UPDATE
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                CustomerVisitor::class,
                'shoppingLists',
                'extend',
                'bidirectional',
                true
            )
        );
    }
}
