<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class Transaction implements OptionInterface
{
    const TRANSACTION_TYPE = 'transaction_type';

    const AUTHORIZE = 'authOnlyTransaction';
    const CAPTURE = 'priorAuthCaptureTransaction';
    const CHARGE = 'authCaptureTransaction';

    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Transaction::TRANSACTION_TYPE)
            ->addAllowedValues(
                Transaction::TRANSACTION_TYPE,
                [
                    Transaction::AUTHORIZE,
                    Transaction::CAPTURE,
                    Transaction::CHARGE,
                ]
            );
    }
}
