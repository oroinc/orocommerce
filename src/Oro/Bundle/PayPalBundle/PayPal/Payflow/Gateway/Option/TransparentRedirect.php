<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractBooleanOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class TransparentRedirect extends AbstractBooleanOption
{
    const SILENTTRAN = 'SILENTTRAN';

    /** {@inheritdoc} */
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
