<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Extension;

use Oro\Bundle\SaleBundle\Form\Type\QuoteType;

/**
 * Form extension that applies payment term ACL restrictions to quote forms.
 *
 * This extension extends the {@see QuoteType} form to enforce access control rules
 * for payment term fields based on user permissions.
 */
class QuotePaymentTermAclExtension extends AbstractPaymentTermAclExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [QuoteType::class];
    }
}
