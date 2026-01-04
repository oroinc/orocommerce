<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Customer options
 */
class Customer extends AbstractOption
{
    public const ACCT = 'ACCT';
    public const CUSTCODE = 'CUSTCODE';
    public const CUSTREF = 'CUSTREF';
    public const EMAIL = 'EMAIL';

    /** @var bool */
    private $customerRequired;

    /**
     * @param bool $customerRequired
     */
    public function __construct($customerRequired = true)
    {
        $this->customerRequired = $customerRequired;
    }

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        if ($this->customerRequired) {
            $resolver->setRequired(Customer::ACCT);
        }

        $options = [self::ACCT, self::CUSTCODE, self::CUSTREF, self::EMAIL];
        $resolver->setDefined($options);

        foreach ($options as $option) {
            $resolver->setAllowedTypes($option, 'string');
        }
    }
}
