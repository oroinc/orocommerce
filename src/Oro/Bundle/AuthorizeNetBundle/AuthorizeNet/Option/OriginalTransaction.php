<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class OriginalTransaction implements OptionsDependentInterface
{
    const ORIGINAL_TRANSACTION = 'original_transaction';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(OriginalTransaction::ORIGINAL_TRANSACTION)
            ->addAllowedTypes(OriginalTransaction::ORIGINAL_TRANSACTION, ['integer','string']);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableDependent(array $options)
    {
        if (!isset($options[Transaction::TRANSACTION_TYPE])) {
            return false;
        }

        return $options[Transaction::TRANSACTION_TYPE] === Transaction::CAPTURE;
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        $this->configureOption($resolver);
    }
}
