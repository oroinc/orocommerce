<?php

namespace OroB2B\Bundle\CheckoutBundle\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

class CheckoutLineItemsManager
{
    /**
     * @var CheckoutDataProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var CheckoutLineItemsConverter
     */
    protected $checkoutLineItemsConverter;

    /**
     * @param CheckoutLineItemsConverter $checkoutLineItemsConverter
     */
    public function __construct(CheckoutLineItemsConverter $checkoutLineItemsConverter)
    {
        $this->checkoutLineItemsConverter = $checkoutLineItemsConverter;
    }

    /**
     * @param CheckoutDataProviderInterface $provider
     */
    public function addProvider(CheckoutDataProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param CheckoutInterface $checkout
     * @return Collection|OrderLineItem[]
     */
    public function getData(CheckoutInterface $checkout)
    {
        $entity = $checkout->getSourceEntity();

        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($entity)) {
                return $this->checkoutLineItemsConverter->convert($provider->getData($entity));
            }
        }

        return new ArrayCollection();
    }
}
