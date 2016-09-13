<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;

class PriceListProductPricesEntityListener
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PriceListToProduct $priceListToProduct
     */
    public function postRemove(PriceListToProduct $priceListToProduct)
    {
        $priceList = $priceListToProduct->getPriceList();
        $product = $priceListToProduct->getProduct();

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroPricingBundle:ProductPrice');
        $prices = $em->getRepository('OroPricingBundle:ProductPrice')
            ->findBy([
                'priceList' => $priceList,
                'product' => $product
            ]);

        foreach ($prices as $price) {
            $em->remove($price);
        }

        $em->flush();
    }
}
