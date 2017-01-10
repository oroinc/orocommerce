<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Customer extends AbstractOption
{
    const ACCT = 'ACCT';

    /** @var bool */
    private $customerRequired;

    /**
     * @param bool $customerRequired
     */
    public function __construct($customerRequired = true)
    {
        $this->customerRequired = $customerRequired;
    }

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        if ($this->customerRequired) {
            $resolver->setRequired(Customer::ACCT);
        }

        $resolver
            ->setDefined(Customer::ACCT)
            ->addAllowedTypes(Customer::ACCT, 'string');
    }
}
