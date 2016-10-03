<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductVisibilityFallbackCategoryData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $products = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_3,
        LoadProductData::PRODUCT_4,
        LoadProductData::PRODUCT_5,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
        LoadProductData::PRODUCT_8,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryVisibilityData::class,
            LoadCategoryProductData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;

        foreach ($this->products as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $this->createProductVisibility($product);
        }

        $this->em->flush();
    }


    /**
     * @param Product $product
     */
    protected function createProductVisibility(Product $product)
    {
        $productVisibility = (new ProductVisibility())
            ->setProduct($product)
            ->setVisibility(ProductVisibility::CATEGORY);

        $this->em->persist($productVisibility);
    }

    /**
     * @param Product $product
     */
    protected function createAccountGroupProductVisibilityResolved(Product $product)
    {
        $accountGroupVisibility = (new AccountGroupProductVisibility())
            ->setProduct($product)
            ->setVisibility(AccountGroupProductVisibility::CATEGORY);

        $this->em->persist($accountGroupVisibility);
    }

    /**
     * @param Product $product
     */
    protected function createAccountProductVisibilityResolved(Product $product)
    {
        $accountVisibility = (new AccountProductVisibility())
            ->setProduct($product)
            ->setVisibility(AccountProductVisibility::CATEGORY);

        $this->em->persist($accountVisibility);
    }
}
