<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v7_0_0_1;

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
 * Adds freeFormTaxCode relation to OrderLineItem entity.
 */
class AddFreeFormTaxCodeToOrderLineItem implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addFreeFormTaxCodeRelation($schema);
    }

    private function addFreeFormTaxCodeRelation(Schema $schema): void
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_order_line_item',
            'freeFormTaxCode',
            'oro_tax_product_tax_code',
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'entity' => ['label' => 'oro.order.orderlineitem.free_form_tax_code.label'],
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'nullable' => true,
                ],
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                ],
                'form' => [
                    // The field is added to the form via OrderLineItemDraftTypeTaxExtension.
                    'is_enabled' => false
                ],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => true],
                'dataaudit' => ['auditable' => true],
                'importexport' => ['excluded' => true],
            ]
        );
    }
}
