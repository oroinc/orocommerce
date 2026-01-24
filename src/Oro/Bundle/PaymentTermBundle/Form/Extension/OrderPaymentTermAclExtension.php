<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Type\OrderType;

/**
 * Form extension that applies payment term ACL restrictions to order forms.
 *
 * This extension extends the {@see OrderType} form to enforce access control rules
 * for payment term fields based on user permissions.
 */
class OrderPaymentTermAclExtension extends AbstractPaymentTermAclExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
