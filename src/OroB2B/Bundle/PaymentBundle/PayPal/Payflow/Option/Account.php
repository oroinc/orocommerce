<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Account extends AbstractOption
{
    const ACCT = 'ACCT';

    /** @var bool */
    private $accountRequired;

    /**
     * @param bool $accountRequired
     */
    public function __construct($accountRequired = true)
    {
        $this->accountRequired = $accountRequired;
    }

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        if ($this->accountRequired) {
            $resolver->setRequired(Account::ACCT);
        }

        $resolver
            ->setDefined(Account::ACCT)
            ->addAllowedTypes(Account::ACCT, 'string');
    }
}
