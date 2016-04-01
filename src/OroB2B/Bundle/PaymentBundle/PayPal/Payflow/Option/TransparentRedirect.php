<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

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
