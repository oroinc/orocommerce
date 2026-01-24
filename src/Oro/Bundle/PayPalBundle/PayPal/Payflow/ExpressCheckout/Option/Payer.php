<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures payer ID option for PayPal Express Checkout transactions.
 *
 * Manages the payer ID parameter, required only for DO_EC actions to identify
 * the customer completing the Express Checkout transaction.
 */
class Payer extends AbstractOption implements OptionsDependentInterface
{
    const PAYERID = 'PAYERID';

    #[\Override]
    public function isApplicableDependent(array $options)
    {
        return isset($options[Action::ACTION]) && $options[Action::ACTION] === Action::DO_EC;
    }

    #[\Override]
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        $resolver
            ->setDefined(Payer::PAYERID)
            ->addAllowedTypes(Payer::PAYERID, 'string');
    }
}
