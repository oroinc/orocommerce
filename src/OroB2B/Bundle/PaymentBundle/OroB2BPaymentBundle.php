<?php

namespace OroB2B\Bundle\PaymentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class OroB2BPaymentBundle extends Bundle
{
    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroB2BPaymentExtension();
    }
}
