<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

class UpdateOrderPaymentTermConfig implements Migration, PaymentTermExtensionAwareInterface
{
    use PaymentTermExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $entityClass = 'Oro\Bundle\OrderBundle\Entity\Order';
        $associationName = $this->paymentTermExtension->getAssociationName($schema);
        $queries->addPostQuery(new UpdateEntityConfigFieldValueQuery(
            $entityClass,
            $associationName,
            'datagrid',
            'is_visible',
            DatagridScope::IS_VISIBLE_HIDDEN
        ));
        $queries->addPostQuery(new UpdateEntityConfigFieldValueQuery(
            $entityClass,
            $associationName,
            'datagrid',
            'show_filter',
            false
        ));
    }
}
