<?php

namespace OroB2B\Component\Checkout\DataProvider;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Component\Checkout\Model\DTO\EntitySummaryDTO;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class CheckoutDataProviderManager
{
    /** @var  CheckoutDataProviderInterface[] */
    protected $providers;

    // TODO: Remove after Entity would be completed
    /** @var  ManagerRegistry */
    protected $reg;

    /**
     * CheckoutDataProviderManager constructor.
     * @param ManagerRegistry $reg
     */
    public function __construct(ManagerRegistry $reg)
    {
        $this->reg = $reg;
    }


    /**
     * @param CheckoutDataProviderInterface $provider
     * @param string $alias
     */
    public function addProvider(CheckoutDataProviderInterface $provider, $alias)
    {
        $this->providers[$alias] = $provider;
    }

    /**
     * @param Checkout $checkout
     * @return bool|EntitySummaryDTO
     */
    public function getData(Checkout $checkout)
    {
        $entity = $this->reg->getRepository('OroB2BShoppingListBundle:ShoppingList')->findOneBy([]);
//        $entity = $checkout->getSourceEntity();
        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($entity)) {
                return $provider->getData($entity);
            }
        }

        return false;
    }
}
