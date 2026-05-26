<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v6_1_9_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds requestProduct many-to-one extended relation to OrderLineItem.
 */
class AddRequestProductToOrderLineItem implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order_line_item');
        if (!$table->hasColumn('requestproduct_id')) {
            $this->addRequestProductToOrderLineItem($schema);
        }
    }

    private function addRequestProductToOrderLineItem(Schema $schema): void
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_order_line_item',
            'requestProduct',
            'oro_rfp_request_product',
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'entity' => [
                    'label' => 'oro.order.orderlineitem.request_product.label',
                    'description' => 'oro.order.orderlineitem.request_product.description',
                ],
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'nullable' => true,
                    'on_delete' => 'SET NULL',
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false],
                'dataaudit' => ['auditable' => false],
                'importexport' => ['excluded' => true],
            ]
        );
    }
}
