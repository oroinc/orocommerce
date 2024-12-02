<?php

namespace Oro\Bundle\PromotionBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Type\SubOrderType;

/**
 * Adds applied promotions to sub order form.
 */
class SubOrderTypeExtension extends OrderTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [SubOrderType::class];
    }
}
