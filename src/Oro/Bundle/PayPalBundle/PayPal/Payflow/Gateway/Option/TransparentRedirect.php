<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractBooleanOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures transparent redirect option for PayPal Payflow Gateway transactions.
 *
 * Controls whether to use silent transaction mode for transparent redirect payment processing,
 * suppressing the default Payflow response page.
 */
class TransparentRedirect extends AbstractBooleanOption
{
    public const SILENTTRAN = 'SILENTTRAN';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(TransparentRedirect::SILENTTRAN)
            ->setNormalizer(
                TransparentRedirect::SILENTTRAN,
                $this->getNormalizer(TransparentRedirect::TRUE, TransparentRedirect::FALSE)
            );
    }
}
