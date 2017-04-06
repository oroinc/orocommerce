<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class Amount implements OptionInterface
{
    const AMOUNT = 'amount';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver->setRequired(Amount::AMOUNT)
            ->addAllowedTypes(Amount::AMOUNT, ['float', 'integer']);
    }
}
