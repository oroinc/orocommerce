<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Request\Stub;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\AbstractRequest;

class NoResolverAbstractRequestStub extends AbstractRequest
{
    /** {@inheritdoc} */
    public function configureOptions(Option\OptionsResolver $resolver)
    {
        $this->addOption(new Option\User());
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return 'some_action';
    }
}
