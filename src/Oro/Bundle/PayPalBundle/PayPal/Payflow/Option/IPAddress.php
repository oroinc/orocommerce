<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures customer IP address option for PayPal Payflow transactions.
 *
 * Stores the customer's IP address for fraud detection and transaction tracking.
 */
class IPAddress implements OptionInterface
{
    public const CUSTIP = 'CUSTIP';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(IPAddress::CUSTIP)
            ->addAllowedTypes(IPAddress::CUSTIP, 'string');
    }
}
