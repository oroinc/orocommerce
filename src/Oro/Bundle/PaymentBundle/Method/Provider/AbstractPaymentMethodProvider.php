<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;

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
     * @param string $identifier
     *
     * @return bool
     */
    public function hasPaymentMethod($identifier)
    {
        return $this->getMethods()->containsKey($identifier);
    }

    /**
     * @param string $identifier
     *
     * @return PaymentMethodInterface|null
     */
    public function getPaymentMethod($identifier)
    {
        if (!$this->hasPaymentMethod($identifier)) {
            return null;
        }

        return $this->getMethods()->get($identifier);
    }

    /**
     * @return array|PaymentMethodInterface[]
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
