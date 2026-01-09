<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

/**
 * Provides common functionality for payment method providers.
 *
 * This base class implements lazy loading of payment methods through the collectMethods template method.
 * It provides a collection-based storage mechanism and standard lookup operations for payment methods.
 * Subclasses must implement the collectMethods method to populate the methods collection
 * with their specific payment method instances.
 */
abstract class AbstractPaymentMethodProvider implements PaymentMethodProviderInterface
{
    /**
     * @var ArrayCollection|PaymentMethodInterface[]
     */
    protected $methods;

    /**
     * Save methods to $methods property
     */
    abstract protected function collectMethods();

    public function __construct()
    {
        $this->methods = new ArrayCollection();
    }

    #[\Override]
    public function hasPaymentMethod($identifier)
    {
        return $this->getMethods()->containsKey($identifier);
    }

    #[\Override]
    public function getPaymentMethod($identifier)
    {
        if (!$this->hasPaymentMethod($identifier)) {
            return null;
        }

        return $this->getMethods()->get($identifier);
    }

    #[\Override]
    public function getPaymentMethods()
    {
        return $this->getMethods()->toArray();
    }

    /**
     * @return ArrayCollection|PaymentMethodInterface[]
     */
    protected function getMethods()
    {
        if ($this->methods->isEmpty()) {
            $this->collectMethods();
        }

        return $this->methods;
    }

    /**
     * @param string $identifier
     * @param PaymentMethodInterface $method
     */
    protected function addMethod($identifier, PaymentMethodInterface $method)
    {
        $this->methods->set($identifier, $method);
    }
}
