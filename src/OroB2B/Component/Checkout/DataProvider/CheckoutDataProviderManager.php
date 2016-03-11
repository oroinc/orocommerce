<?php

namespace OroB2B\Component\Checkout\DataProvider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutDataProviderManager
{
    /** @var  CheckoutDataProviderInterface[] */
    protected $providers;

    /**
     * @param CheckoutDataProviderInterface $provider
     */
    public function addProvider(CheckoutDataProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param Checkout $checkout
     * @return bool|array
     */
    public function getData(Checkout $checkout)
    {
        $entity = $checkout->getSourceEntity();
        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($entity)) {
                return $provider->getData($entity);
            }
        }

        return false;
    }
}
