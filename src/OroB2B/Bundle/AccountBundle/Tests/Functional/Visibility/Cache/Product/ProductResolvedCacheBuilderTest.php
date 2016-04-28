<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\ProductResolvedCacheBuilder;
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

        $entityManager = $this->getManagerForVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $resolvedVisibility = $this->getVisibilityResolved();
        $this->assertNotNull($resolvedVisibility);
        $this->assertStatic($resolvedVisibility, $visibility, BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
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
        $this->assertEquals(
            $resolvedVisibility->getVisibility(),
            BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );
        $this->assertProductIdentifyEntitiesAccessory($resolvedVisibility);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            'without_website' => [
                'expectedStaticCount' => 3,
                'expectedCategoryCount' => 24,
                'websiteReference' => null,
            ],
            'with_website1' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 0,
                'websiteReference' => LoadWebsiteData::WEBSITE1,
            ],
            'with_website2' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 0,
                'websiteReference' => LoadWebsiteData::WEBSITE2,
            ],
        ];
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
        $entityManager = $this->getManagerForVisibilityResolved();
        $entity = $entityManager
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findByPrimaryKey($this->product, $this->website);

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
     * @return ProductRepository
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
    protected function getCacheBuilder()
    {
        $container = $this->client->getContainer();

        $builder = new ProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $builder->setCacheClass(
            $container->getParameter('orob2b_account.entity.product_visibility_resolved.class')
        );

        return $builder;
    }
}
