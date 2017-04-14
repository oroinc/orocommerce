<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

interface SupportsEntityPaymentContextFactoryInterface
{
    /**
     * @param string $entityClass
     * @param int $entityId
     *
     * @return PaymentContextInterface|null
     */
    public function create($entityClass, $entityId);

    /**
     * @param string $entityClass
     * @param int $entityId
     *
     * @return bool
     */
    public function supports($entityClass, $entityId);
}
