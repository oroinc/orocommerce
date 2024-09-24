<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class SilentPost implements OptionInterface
{
    const SILENTPOSTURL = 'SILENTPOSTURL';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SilentPost::SILENTPOSTURL)
            ->addAllowedTypes(SilentPost::SILENTPOSTURL, 'string');
    }
}
