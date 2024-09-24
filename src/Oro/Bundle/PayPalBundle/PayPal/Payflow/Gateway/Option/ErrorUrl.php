<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class ErrorUrl implements OptionInterface
{
    const ERRORURL = 'ERRORURL';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(ErrorUrl::ERRORURL)
            ->addAllowedTypes(ErrorUrl::ERRORURL, 'string');
    }
}
