<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class ButtonSource extends AbstractOption
{
    const BUTTONSOURCE = 'BUTTONSOURCE';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(ButtonSource::BUTTONSOURCE)
            ->addAllowedTypes(ButtonSource::BUTTONSOURCE, 'string');
    }
}
