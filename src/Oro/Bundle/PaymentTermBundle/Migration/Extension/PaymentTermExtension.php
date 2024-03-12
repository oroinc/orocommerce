<?php

namespace Oro\Bundle\PaymentTermBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Provides an ability to create payment term related associations.
 */
class PaymentTermExtension implements ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    /**
     * Adds the association between the target table and the payment term table.
     */
    public function addPaymentTermAssociation(Schema $schema, string $targetTableName, array $options = []): void
    {
        $targetTable = $schema->getTable($targetTableName);

        $paymentTermTable = $schema->getTable('oro_payment_term');
        $associationName = $this->getAssociationNameByTable($paymentTermTable);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $targetTable,
            $associationName,
            $paymentTermTable,
            'label',
            ArrayUtil::arrayMergeRecursiveDistinct(
                [
                    'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'entity' => ['label' => 'oro.paymentterm.entity_label'],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_TRUE,
                        'show_filter' => true,
                    ],
                    'form' => [
                        'is_enabled' => true,
                        'form_type' => PaymentTermSelectType::class,
                    ],
                    'view' => ['is_displayable' => true],
                    'dataaudit' => ['auditable' => true],
                ],
                $options
            )
        );
    }

    public function getAssociationName(Schema $schema): string
    {
        return $this->getAssociationNameByTable($schema->getTable('oro_payment_term'));
    }

    private function getAssociationNameByTable(Table $paymentTermTable): string
    {
        return ExtendHelper::buildAssociationName(
            $this->extendExtension->getEntityClassByTableName($paymentTermTable->getName())
        );
    }
}
