<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\Factory\Exception\UnsupportedEntityException;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * Defines the contract for payment context factories that support specific entity types.
 *
 * Implementations of this interface are responsible for creating payment contexts for
 * specific entity classes and checking whether they support a given entity type.
 */
interface SupportsEntityPaymentContextFactoryInterface
{
    /**
     * @param string $entityClass
     * @param int $entityId
     *
     * @throws UnsupportedEntityException
     *
     * @return PaymentContextInterface
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
