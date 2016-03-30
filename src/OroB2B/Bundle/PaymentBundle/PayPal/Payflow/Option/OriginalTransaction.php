<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class OriginalTransaction extends AbstractOption
{
    const ORIGID = 'ORIGID';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(OriginalTransaction::ORIGID)
            ->addAllowedTypes(OriginalTransaction::ORIGID, 'string');
    }
}
