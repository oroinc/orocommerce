<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class Amount implements OptionsDependentInterface
{
    const AMOUNT = 'amount';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver->setDefined(Amount::AMOUNT)
            ->addAllowedTypes(Amount::AMOUNT, ['float', 'integer']);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableDependent(array $options)
    {
        if (!isset($options[Transaction::TRANSACTION_TYPE])) {
            return false;
        }

        return in_array(
            $options[Transaction::TRANSACTION_TYPE],
            [Transaction::CHARGE, Transaction::AUTHORIZE],
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        $this->configureOption($resolver);
    }
}
