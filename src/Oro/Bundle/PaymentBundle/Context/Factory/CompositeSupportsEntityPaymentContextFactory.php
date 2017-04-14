<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\Factory\Exception\UnsupportedEntity;

class CompositeSupportsEntityPaymentContextFactory implements SupportsEntityPaymentContextFactoryInterface
{
    /**
     * @var SupportsEntityPaymentContextFactoryInterface[]
     */
    private $factories;

    /**
     * @param array $factories
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     * {@inheritdoc}
     */
    public function create($entityClass, $entityId)
    {
        return $this->getFactory($entityClass, $entityId)->create($entityClass, $entityId);
    }

    /**
     * {@inheritdoc}
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
     * @param $entityClass
     * @param $entityId
     *
     * @return SupportsEntityPaymentContextFactoryInterface
     *
     * @throws \Oro\Bundle\PaymentBundle\Context\Factory\Exception\UnsupportedEntity
     */
    protected function getFactory($entityClass, $entityId)
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($entityClass, $entityId)) {
                return $factory;
            }
        }

        $msg = sprintf(
            'Could not find payment context factory for given entity class (%s) and id (%d)',
            $entityClass,
            $entityId
        );
        throw new UnsupportedEntity($msg);
    }
}
