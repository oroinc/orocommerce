<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

abstract class AbstractChangedProductPriceTest extends WebTestCase
{
    protected function clearTable()
    {
        $this->getProductPriceChangeTriggerRepository()
            ->createQueryBuilder('cpp')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @return Product
     */
    protected function getProduct()
    {
        $productClassName = $this->getContainer()->getParameter('orob2b_product.entity.product.class');

        $manager = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager($productClassName);
        /** @var ObjectRepository $repository */
        $repository = $manager->getRepository('OroB2BProductBundle:Product');

        return $repository->findOneBy([]);
    }

    /**
     * @return PriceList
     */
    protected function getPriceList()
    {
        $priceListClassName = $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class');

        $manager = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager($priceListClassName);
        $repository = $manager->getRepository('OroB2BPricingBundle:PriceList');

        return $repository->findOneBy([]);
    }

    /**
     * @return ProductUnit
     */
    protected function getProductUnit()
    {
        $productUnitClassName = $this->getContainer()->getParameter('orob2b_product.entity.product_unit.class');

        $manager = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager($productUnitClassName);
        $repository = $manager->getRepository('OroB2BProductBundle:ProductUnit');

        return $repository->findOneBy([]);
    }

    /**
     * @return EntityManager
     */
    protected function getProductPriceManager()
    {
        $productPriceClassName = $this->getContainer()->getParameter('orob2b_pricing.entity.product_price.class');

        return $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager($productPriceClassName);
    }

    /**
     * @return ObjectRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->getProductPriceManager()->getRepository('OroB2BPricingBundle:ProductPrice');
    }

    /**
     * @return ProductPrice
     */
    protected function getProductPrice()
    {
        return $this->getProductPriceRepository()->findOneBy([]);
    }

    /**
     * @return ObjectManager
     */
    protected function getProductPriceChangeTriggerManager()
    {
        $changedProductPriceClassName = $this->getContainer()
            ->getParameter('orob2b_pricing.entity.product_price_change_trigger.class');

        return $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManager($changedProductPriceClassName);
    }

    /**
     * @return ProductPriceChangeTriggerRepository
     */
    protected function getProductPriceChangeTriggerRepository()
    {
        return $this->getProductPriceChangeTriggerManager()
            ->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger');
    }
}
