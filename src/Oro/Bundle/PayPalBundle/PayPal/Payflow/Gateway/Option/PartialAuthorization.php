<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractBooleanOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class PartialAuthorization extends AbstractBooleanOption
{
    const PARTIALAUTH = 'PARTIALAUTH';

    /** {@inheritdoc} */
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
