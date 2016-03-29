<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Amount implements OptionInterface
{
    const AMT = 'AMT';

    const ITEMAMT = 'ITEMAMT';
    const TAXAMT = 'TAXAMT';
    const FREIGHTAMT = 'FREIGHTAMT';
    const HANDLINGAMT = 'HANDLINGAMT';
    const INSURANCEAMT = 'INSURANCEAMT';
    const DISCOUNT = 'DISCOUNT';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $allowedTypes = ['string', 'integer', 'float'];
        $resolver
            ->setDefined(Amount::AMT)
            ->setDefault(Amount::ITEMAMT, 0)
            ->setDefault(Amount::TAXAMT, 0)
            ->setDefault(Amount::FREIGHTAMT, 0)
            ->setDefault(Amount::HANDLINGAMT, 0)
            ->setDefault(Amount::INSURANCEAMT, 0)
            ->setDefault(Amount::DISCOUNT, 0)
            ->addAllowedTypes(Amount::AMT, $allowedTypes)
            ->addAllowedTypes(Amount::ITEMAMT, $allowedTypes)
            ->addAllowedTypes(Amount::TAXAMT, $allowedTypes)
            ->addAllowedTypes(Amount::FREIGHTAMT, $allowedTypes)
            ->addAllowedTypes(Amount::HANDLINGAMT, $allowedTypes)
            ->addAllowedTypes(Amount::INSURANCEAMT, $allowedTypes)
            ->addAllowedTypes(Amount::DISCOUNT, $allowedTypes)
            ->setNormalizer(
                Amount::AMT,
                function (Options $options, $amount) {
                    if (!$amount) {
                        $amounts = [
                            $options[Amount::ITEMAMT],
                            $options[Amount::TAXAMT],
                            $options[Amount::FREIGHTAMT],
                            $options[Amount::HANDLINGAMT],
                            $options[Amount::INSURANCEAMT],
                            $options[Amount::DISCOUNT],
                        ];

                        $amount = array_sum(array_filter($amounts));
                    }

                    return $amount;
                }
            );
    }
}
