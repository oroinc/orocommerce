<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Bridge\Doctrine\RegistryInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;

class PriceListProductEntityListener
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
     * @param LifecycleEventArgs $args
     */
    public function postRemove(PriceListToProduct $priceListToProduct, LifecycleEventArgs $args)
    {
        $priceList = $priceListToProduct->getPriceList();
        $product = $priceListToProduct->getProduct();

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BPricingBundle:ProductPrice');
        $prices = $em->getRepository('OroB2BPricingBundle:ProductPrice')
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
