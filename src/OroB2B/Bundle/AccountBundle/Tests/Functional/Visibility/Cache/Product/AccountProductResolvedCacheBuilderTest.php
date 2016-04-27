<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AccountProductResolvedCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    public function testChangeAccountProductVisibilityToHidden()
    {
        $visibility = new AccountProductVisibility();
        $visibility->setWebsite($this->website);
        $visibility->setProduct($this->product);
        $visibility->setAccount($this->account);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeAccountProductVisibilityToHidden
     */
    public function testChangeAccountProductVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeAccountProductVisibilityToVisible
     */
    public function testChangeAccountProductVisibilityToCategory()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals($visibility, $visibilityResolved->getSourceProductVisibility());
        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_CATEGORY, $visibilityResolved->getSource());
        $this->assertEquals(
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
            $visibilityResolved->getCategory()->getId()
        );
        $this->assertEquals(
            BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $visibilityResolved->getVisibility()
        );
        $this->assertProductIdentifyEntitiesAccessory($visibilityResolved);
    }

    /**
     * @depends testChangeAccountProductVisibilityToCategory
     */
    public function testChangeAccountProductVisibilityToAccountGroup()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountProductVisibility::ACCOUNT_GROUP);

        $this->assertNotNull($this->getVisibilityResolved());

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $this->assertNull($this->getVisibilityResolved());
    }

    /**
     * @depends testChangeAccountProductVisibilityToAccountGroup
     */
    public function testChangeAccountProductVisibilityToCurrentProduct()
    {
        // prepare product visibility entity
        $productVisibility = new ProductVisibility();
        $productVisibility->setProduct($this->product)
            ->setWebsite($this->website)
            ->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForProductVisibility();
        $entityManager->persist($productVisibility);
        $entityManager->flush();

        // create new visibility because old one was automatically removed
        $visibility = new AccountProductVisibility();
        $visibility->setWebsite($this->website);
        $visibility->setProduct($this->product);
        $visibility->setAccount($this->account);
        $visibility->setVisibility(AccountProductVisibility::CURRENT_PRODUCT);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals($visibility, $visibilityResolved->getSourceProductVisibility());
        $this->assertNull($visibilityResolved->getCategory());
        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_STATIC, $visibilityResolved->getSource());
        $this->assertEquals(
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $visibilityResolved->getVisibility()
        );
        $this->assertProductIdentifyEntitiesAccessory($visibilityResolved);
    }

    /**
     * @depends testChangeAccountProductVisibilityToCurrentProduct
     */
    public function testChangeAccountProductVisibilityToCurrentProductWithoutResolvedFallbackEntity()
    {
        // remove fallback to product to test only this builder
        $visibility = $this->getVisibility();
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        // remove product visibility (i.e. fallback to config)
        $productVisibility = $this->getProductVisibility();
        $productVisibility->setVisibility(ProductVisibility::CONFIG);

        $productEntityManager = $this->getManagerForProductVisibility();
        $productEntityManager->flush();

        // assert account visibility fallback to config
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountProductVisibility::CURRENT_PRODUCT);

        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals($visibility, $visibilityResolved->getSourceProductVisibility());
        $this->assertNull($visibilityResolved->getCategory());
        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_STATIC, $visibilityResolved->getSource());
        $this->assertEquals(
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $visibilityResolved->getVisibility()
        );
        $this->assertProductIdentifyEntitiesAccessory($visibilityResolved);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            'without_website' => [
                'expectedStaticCount' => 5,
                'expectedCategoryCount' => 1,
                'websiteReference' => null,
            ],
            'with_default_website' => [
                'expectedStaticCount' => 4,
                'expectedCategoryCount' => 0,
                'websiteReference' => 'default',
            ],
            'with_website1' => [
                'expectedStaticCount' => 1,
                'expectedCategoryCount' => 1,
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
     * @param BaseProductVisibilityResolved|AccountProductVisibilityResolved $visibilityResolved
     */
    protected function assertProductIdentifyEntitiesAccessory(BaseProductVisibilityResolved $visibilityResolved)
    {
        parent::assertProductIdentifyEntitiesAccessory($visibilityResolved);
        $this->assertEquals($this->account, $visibilityResolved->getAccount());
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountProductVisibility');
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForVisibilityResolved()
    {
        return $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForProductVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\ProductVisibility');
    }

    /**
     * @return null|AccountProductVisibility
     */
    protected function getVisibility()
    {
        return $this->getManagerForVisibility()
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product, 'account' => $this->account]);
    }

    /**
     * @return null|AccountProductVisibilityResolved
     */
    protected function getVisibilityResolved()
    {
        $entityManager = $this->getManagerForVisibilityResolved();
        $entity = $entityManager
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findByPrimaryKey($this->account, $this->product, $this->website);

        if ($entity) {
            $entityManager->refresh($entity);
        }

        return $entity;
    }

    /**
     * @return null|ProductVisibility
     */
    protected function getProductVisibility()
    {
        return $this->getManagerForProductVisibility()
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product]);
    }

    /**
     * @inheritdoc
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:Visibility\AccountProductVisibility'
        );
    }

    /**
     * @return AccountProductRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder()
    {
        $container = $this->client->getContainer();

        $builder = new AccountProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $builder->setCacheClass(
            $container->getParameter('orob2b_account.entity.account_product_visibility_resolved.class')
        );

        return $builder;
    }
}
