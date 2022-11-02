<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\Factory\Exception\UnsupportedEntityException;

/**
 * Delegates creation of the payment context to child factories.
 */
class CompositeSupportsEntityPaymentContextFactory implements SupportsEntityPaymentContextFactoryInterface
{
    /** @var iterable|SupportsEntityPaymentContextFactoryInterface[] */
    private $factories;

    /**
     * @param iterable|SupportsEntityPaymentContextFactoryInterface[] $factories
     */
    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    /**
     * {@inheritDoc}
     */
    public function create($entityClass, $entityId)
    {
        return $this->getFactory($entityClass, $entityId)->create($entityClass, $entityId);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($entityClass, $entityId)
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($entityClass, $entityId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return SupportsEntityPaymentContextFactoryInterface
     *
     * @throws UnsupportedEntityException
     */
    private function getFactory($entityClass, $entityId)
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($entityClass, $entityId)) {
                return $factory;
            }
        }

        throw new UnsupportedEntityException(sprintf(
            'Could not find payment context factory for given entity class (%s) and id (%d)',
            $entityClass,
            $entityId
        ));
    }
}
