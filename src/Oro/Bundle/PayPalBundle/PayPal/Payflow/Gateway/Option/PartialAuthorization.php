<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractBooleanOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures partial authorization option for PayPal Payflow Gateway transactions.
 *
 * Controls whether to allow partial authorization when the full requested amount
 * is not available.
 */
class PartialAuthorization extends AbstractBooleanOption
{
    const PARTIALAUTH = 'PARTIALAUTH';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(PartialAuthorization::PARTIALAUTH)
            ->setNormalizer(
                PartialAuthorization::PARTIALAUTH,
                $this->getNormalizer(PartialAuthorization::YES, PartialAuthorization::NO)
            );
    }
}
