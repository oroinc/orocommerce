<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request\Stub;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\AbstractRequest;

class NoResolverAbstractRequestStub extends AbstractRequest
{
    #[\Override]
    public function configureOptions(Option\OptionsResolver $resolver)
    {
        $this->addOption(new Option\User());
    }

    /**
     * @return string
     */
    #[\Override]
    public function getTransactionType()
    {
        return 'some_action';
    }
}
