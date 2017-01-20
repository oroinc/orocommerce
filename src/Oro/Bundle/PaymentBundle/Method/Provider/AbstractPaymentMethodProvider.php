<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

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

    /**
     * {@inheritdoc}
     */
    public function hasPaymentMethod($identifier)
    {
        return $this->getMethods()->containsKey($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethod($identifier)
    {
        if (!$this->hasPaymentMethod($identifier)) {
            return null;
        }

        return $this->getMethods()->get($identifier);
    }

    /**
     * {@inheritdoc}
     */
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
