<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures silent post URL option for PayPal Payflow Gateway transactions.
 *
 * Specifies the URL for receiving silent post notifications of transaction results.
 */
class SilentPost implements OptionInterface
{
    public const SILENTPOSTURL = 'SILENTPOSTURL';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(SilentPost::SILENTPOSTURL)
            ->addAllowedTypes(SilentPost::SILENTPOSTURL, 'string');
    }
}
