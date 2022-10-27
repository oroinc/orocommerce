<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Type\OrderType;

class OrderPaymentTermAclExtension extends AbstractPaymentTermAclExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
