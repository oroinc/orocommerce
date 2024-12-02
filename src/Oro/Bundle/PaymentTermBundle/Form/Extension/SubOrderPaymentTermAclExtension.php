<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Type\SubOrderType;

/**
 * ACL restrictions for SubOrderType form type.
 */
class SubOrderPaymentTermAclExtension extends AbstractPaymentTermAclExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [SubOrderType::class];
    }
}
