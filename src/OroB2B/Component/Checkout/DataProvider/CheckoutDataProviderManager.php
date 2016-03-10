<?php

namespace OroB2B\Component\Checkout\DataProvider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Component\Checkout\Model\DTO\EntitySummaryDTO;

class CheckoutDataProviderManager
{
    /** @var  CheckoutDataProviderInterface[] */
    protected $providers;

    // @TODO: Remove after Entity would be completed
    /** @var  ManagerRegistry */
    protected $reg;

    /**
     * @param ManagerRegistry $reg
     */
    public function __construct(ManagerRegistry $reg)
    {
        $this->reg = $reg;
    }

    /**
     * @param CheckoutDataProviderInterface $provider
     */
    public function addProvider(CheckoutDataProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param Checkout $checkout
     * @return bool|EntitySummaryDTO
     */
    public function getData(Checkout $checkout)
    {
//      @TODO: Remove after Entity would be completed
        $entity = $this->reg->getRepository('OroB2BShoppingListBundle:ShoppingList')->findOneBy([]);
//      @TODO: uncomment after Entity would be completed
//      $entity = $checkout->getSourceEntity();
        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($entity)) {
                return $provider->getData($entity);
            }
        }

        return false;
    }
}
