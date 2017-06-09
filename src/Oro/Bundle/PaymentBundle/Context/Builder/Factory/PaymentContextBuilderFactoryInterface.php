<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder\Factory;

use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;

interface PaymentContextBuilderFactoryInterface
{
    /**
     * @param object           $sourceEntity
     * @param string           $sourceEntityId
     *
     * @return PaymentContextBuilderInterface
     */
    public function createPaymentContextBuilder($sourceEntity, $sourceEntityId);
}
