<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

class UpdatePaymentTermAssociationFormType implements Migration, PaymentTermExtensionAwareInterface
{
    use PaymentTermExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $associationName = $this->paymentTermExtension->getAssociationName($schema);

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                Order::class,
                $associationName,
                'form',
                'form_type',
                PaymentTermSelectType::class,
                'oro_payment_term_select'
            )
        );
    }
}
