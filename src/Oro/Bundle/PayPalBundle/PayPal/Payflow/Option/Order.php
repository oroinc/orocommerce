<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Order options
 */
class Order implements OptionInterface
{
    public const ORDERID = 'ORDERID';

    public function configureOption(OptionsResolver $resolver)
    {
        $resolver->setDefined(self::ORDERID)
            ->setAllowedTypes(self::ORDERID, 'string');
    }
}
