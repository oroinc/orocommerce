<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class ProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    public function testChangeProductVisibilityToHidden()
    {
        // main product visibility entity
        $visibility = new ProductVisibility();
        $visibility->setWebsite($this->website);
        $visibility->setProduct($this->product);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        // account visibility entity with fallback to main product visibility entity
        $accountVisibility = new AccountProductVisibility();
        $accountVisibility->setWebsite($this->website);
        $accountVisibility->setProduct($this->product);
        $accountVisibility->setAccount($this->account);
        $accountVisibility->setVisibility(AccountProductVisibility::CURRENT_PRODUCT);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->persist($visibility);
        $entityManager->persist($accountVisibility);
        $entityManager->flush();

        $resolvedVisibility = $this->getVisibilityResolved();
        $this->assertNotNull($resolvedVisibility);
        $this->assertStatic($resolvedVisibility, $visibility, BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
        $this->assertAccountVisibilityResolved(BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeProductVisibilityToHidden
     */
    public function testChangeProductVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $this->assertNotNull($visibility);

        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $resolvedVisibility = $this->getVisibilityResolved();
        $this->assertNotNull($resolvedVisibility);
        $this->assertStatic($resolvedVisibility, $visibility, BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
        $this->assertAccountVisibilityResolved(BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeProductVisibilityToVisible
     */
    public function testChangeProductVisibilityToConfig()
    {
        $visibility = $this->getVisibility();
        $this->assertNotNull($visibility);
        $this->assertNotNull($this->getVisibilityResolved());

        $visibility->setVisibility(ProductVisibility::CONFIG);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $this->assertNull($this->getVisibilityResolved());
        $this->assertAccountVisibilityResolved(BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeProductVisibilityToConfig
     */
    public function testChangeProductVisibilityToCategory()
    {
        $visibility = $this->getVisibility();
        $this->assertNotNull($visibility);

        $visibility->setVisibility(ProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->remove($visibility);
        $entityManager->flush();

        $resolvedVisibility = $this->getVisibilityResolved();
        $this->assertNotNull($resolvedVisibility);
        $this->assertEquals(
            $resolvedVisibility->getCategory()->getId(),
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId()
        );
        $this->assertEquals($resolvedVisibility->getSource(), BaseProductVisibilityResolved::SOURCE_CATEGORY);
        $this->assertNull($resolvedVisibility->getSourceProductVisibility());
        $this->assertEquals($resolvedVisibility->getVisibility(), BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
        $this->assertProductIdentifyEntitiesAccessory($resolvedVisibility);
        $this->assertAccountVisibilityResolved(BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @param int $visibility
     */
    protected function assertAccountVisibilityResolved($visibility)
    {
        $accountVisibilityResolved = $this->getAccountVisibilityResolved();
        $this->assertNotNull($accountVisibilityResolved);
        $this->assertEquals($visibility, $accountVisibilityResolved->getVisibility());
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\ProductVisibility');
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForVisibilityResolved()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }

    /**
     * @return ProductVisibility|null
     */
    protected function getVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product]);
    }

    /**
     * @return ProductVisibilityResolved|null
     */
    protected function getVisibilityResolved()
    {
        return $this->getManagerForVisibilityResolved()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findByPrimaryKey($this->product, $this->website);
    }

    /**
     * @return AccountProductVisibilityResolved|null
     */
    protected function getAccountVisibilityResolved()
    {
        $entityManager = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $entity = $entityManager
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findByPrimaryKey($this->account, $this->product, $this->website);

        if ($entity) {
            $entityManager->refresh($entity);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:Visibility\ProductVisibility'
        );
    }

    /**
     * @return ProductVisibilityResolvedRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved'
        );
    }


    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            'without_website' => [
                'expectedStaticCount' => 4,
                'expectedCategoryCount' => 16,
                'websiteReference' => null,
            ],
            'with_website1' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 5,
                'websiteReference' => LoadWebsiteData::WEBSITE1,
            ],
            'with_website2' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 8,
                'websiteReference' => LoadWebsiteData::WEBSITE2,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder()
    {
        return $this->client
            ->getContainer()
            ->get('orob2b_account.visibility.cache.product.product_resolved_cache_builder');
    }
}
