<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

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
