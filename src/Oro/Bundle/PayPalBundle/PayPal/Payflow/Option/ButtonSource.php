<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures button source option for PayPal Payflow transactions.
 *
 * Identifies the source of the transaction for PayPal tracking and analytics.
 */
class ButtonSource extends AbstractOption
{
    public const BUTTONSOURCE = 'BUTTONSOURCE';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(ButtonSource::BUTTONSOURCE)
            ->addAllowedTypes(ButtonSource::BUTTONSOURCE, 'string');
    }
}
