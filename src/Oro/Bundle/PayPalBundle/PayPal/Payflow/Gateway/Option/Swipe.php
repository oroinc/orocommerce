<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures swipe data option for PayPal Payflow Gateway transactions.
 *
 * Allows submission of magnetic stripe card data for card-present transactions.
 */
class Swipe extends AbstractOption
{
    const SWIPE = 'SWIPE';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Swipe::SWIPE)
            ->addAllowedTypes(Swipe::SWIPE, 'string');
    }
}
