<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

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
