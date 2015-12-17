<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityListener
{
    /**
     * @var ProductCaseCacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $productVisibilityClass;

    /**
     * @param AfterFormProcessEvent $event
     */
    public function onProductCategoryChange(AfterFormProcessEvent $event)
    {
        if (!($event->getData() instanceof Product)) {
            return;
        }
        $product = $event->getData();
        $category = $this->doctrine
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($product);

        if (!$category) {
            /** @var $productVisibilityRepository ProductVisibilityRepository */
            $productVisibilityRepository = $this->doctrine
                ->getManagerForClass($this->productVisibilityClass)
                ->getRepository($this->productVisibilityClass);

            $websites = $this->getWebsites();
            $productVisibilityRepository->setToDefaultWithoutCategoryByProduct($product, $websites);
        }

        $this->cacheBuilder->productCategoryChanged($product);
    }

    /**
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function setCacheBuilder(CacheBuilderInterface $cacheBuilder)
    {
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * @param Registry $doctrine
     */
    public function setRegistry(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param string $productVisibilityClass
     */
    public function setProductVisibilityClass($productVisibilityClass)
    {
        $this->productVisibilityClass = $productVisibilityClass;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|\OroB2B\Bundle\WebsiteBundle\Entity\Website[]
     */
    protected function getWebsites()
    {
        return $this->doctrine
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->getAllWebsites();
    }
}
