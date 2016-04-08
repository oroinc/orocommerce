<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Code implements OptionInterface
{
    const CVV2 = 'CVV2';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Code::CVV2)
            ->addAllowedTypes(Code::CVV2, 'string');
    }
}
