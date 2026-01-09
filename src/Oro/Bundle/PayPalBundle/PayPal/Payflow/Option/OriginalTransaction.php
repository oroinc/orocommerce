<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures original transaction ID option for PayPal Payflow transactions.
 *
 * References a previous transaction for follow-up operations like capture, credit, or void.
 */
class OriginalTransaction extends AbstractOption
{
    public const ORIGID = 'ORIGID';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(OriginalTransaction::ORIGID)
            ->addAllowedTypes(OriginalTransaction::ORIGID, 'string');
    }
}
