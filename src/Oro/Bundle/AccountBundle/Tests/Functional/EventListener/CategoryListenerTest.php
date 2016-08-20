<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class CategoryListenerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $categoryManager;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var Website
     */
    protected $firstWebsite;

    /**
     * @var Website
     */
    protected $secondWebsite;

    protected function setUp()
    {
        $this->initClient();

        $this->categoryManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category');
        $this->categoryRepository = $this->categoryManager
            ->getRepository('OroCatalogBundle:Category');

        $this->loadFixtures(['Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);

        $this->firstWebsite = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->secondWebsite = $this->getReference(LoadWebsiteData::WEBSITE2);
    }

    public function testChangeProductCategory()
    {
        /** @var $product Product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $previousCategory = $this->categoryRepository->findOneByProduct($product);

        // default value is categort fallback
        $this->assertProductVisibility($this->firstWebsite, $product, null, $previousCategory);
        $this->assertProductVisibility($this->secondWebsite, $product, null, $previousCategory);

        /** @var $newCategory Category */
        $newCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->categoryManager->refresh($newCategory);

        $previousCategory->removeProduct($product);
        $newCategory->addProduct($product);
        $this->categoryManager->flush();

        // category has been changed
        $this->assertProductVisibility($this->firstWebsite, $product, null, $newCategory);
        $this->assertProductVisibility($this->secondWebsite, $product, null, $newCategory);
    }

    public function testRemoveProductFromCategoryAndAddProductToCategory()
    {
        /** @var $product Product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $category = $this->categoryRepository->findOneByProduct($product);

        // default value is category fallback
        $this->assertProductVisibility($this->firstWebsite, $product, null, $category);
        $this->assertProductVisibility($this->secondWebsite, $product, null, $category);

        $category->removeProduct($product);
        $this->categoryManager->flush();

        // fallback changed to config
        $this->assertProductVisibility($this->firstWebsite, $product, ProductVisibility::CONFIG);
        $this->assertProductVisibility($this->secondWebsite, $product, ProductVisibility::CONFIG);

        $category->addProduct($product);
        $this->categoryManager->flush();

        // fallback didn't return back because it was changed during category removal
        $this->assertProductVisibility($this->firstWebsite, $product, ProductVisibility::CONFIG);
        $this->assertProductVisibility($this->secondWebsite, $product, ProductVisibility::CONFIG);
    }

    /**
     * @param Website $website
     * @param Product $product
     * @param string|null $visibilityCode
     * @param Category|null $category
     */
    protected function assertProductVisibility(
        Website $website,
        Product $product,
        $visibilityCode = null,
        Category $category = null
    ) {
        $visibility = $this->getVisibility($website, $product);
        if ($visibilityCode) {
            $this->assertNotNull($visibility);
            $this->assertEquals($visibilityCode, $visibility->getVisibility());
        } else {
            $this->assertNull($visibility);
        }

        $resolvedVisibility = $this->getResolvedVisibility($website, $product);
        if ($category) {
            $this->assertNotNull($resolvedVisibility);
            $this->assertEquals(ProductVisibilityResolved::SOURCE_CATEGORY, $resolvedVisibility->getSource());
            $this->assertEquals($category->getId(), $resolvedVisibility->getCategory()->getId());
        } else {
            $this->assertNull($resolvedVisibility);
        }

    }

    /**
     * @param Website $website
     * @param Product $product
     * @return ProductVisibility|null
     */
    protected function getVisibility(Website $website, Product $product)
    {
        $entityManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:Visibility\ProductVisibility');

        $qb = $entityManager->getRepository('OroAccountBundle:Visibility\ProductVisibility')
            ->createQueryBuilder('v')
            ->andWhere('v.website = :website')
            ->andWhere('v.product = :product')
            ->setParameter('website', $website)
            ->setParameter('product', $product);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Website $website
     * @param Product $product
     * @return ProductVisibilityResolved|null
     */
    protected function getResolvedVisibility(Website $website, Product $product)
    {
        $entityManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved');

        $qb = $entityManager->getRepository('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->createQueryBuilder('v')
            ->andWhere('v.website = :website')
            ->andWhere('v.product = :product')
            ->setParameter('website', $website)
            ->setParameter('product', $product);

        $entity = $qb->getQuery()->getOneOrNullResult();
        if ($entity) {
            $entityManager->refresh($entity);
        }

        return $entity;
    }
}
