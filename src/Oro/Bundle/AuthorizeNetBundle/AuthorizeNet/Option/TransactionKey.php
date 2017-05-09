<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class TransactionKey implements OptionInterface
{
    const TRANSACTION_KEY = 'transaction_key';

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(TransactionKey::TRANSACTION_KEY)
            ->addAllowedTypes(TransactionKey::TRANSACTION_KEY, 'string');
    }
}
