<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\AccountGroupProductResolvedCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountGroupProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    public function testChangeAccountGroupProductVisibilityToHidden()
    {
        $visibility = new AccountGroupProductVisibility();
        $visibility->setWebsite($this->website);
        $visibility->setProduct($this->product);
        $visibility->setAccountGroup($this->accountGroup);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeAccountGroupProductVisibilityToHidden
     */
    public function testChangeAccountGroupProductVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeAccountGroupProductVisibilityToVisible
     */
    public function testChangeAccountGroupProductVisibilityToCategory()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountGroupProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();

        $this->assertEquals(
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
            $visibilityResolved->getCategory()->getId()
        );

        $this->assertEquals(
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
            $visibilityResolved->getCategory()->getId()
        );

        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_CATEGORY, $visibilityResolved->getSource());
        $this->assertEquals($visibility, $visibilityResolved->getSourceProductVisibility());
        $this->assertEquals(
            BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $visibilityResolved->getVisibility()
        );
        $this->assertProductIdentifyEntitiesAccessory($visibilityResolved);
    }

    public function testChangeAccountGroupProductVisibilityToCurrentProduct()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountGroupProductVisibility::CURRENT_PRODUCT);

        $this->assertNotNull($this->getVisibilityResolved());

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $this->assertNull($this->getVisibilityResolved());
    }

    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            'without_website' => [
                'expectedStaticCount' => 6,
                'expectedCategoryCount' => 2,
                'websiteReference' => null,
            ],
            'with_website1' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 2,
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
     * @param BaseProductVisibilityResolved|AccountGroupProductVisibilityResolved $visibilityResolved
     */
    protected function assertProductIdentifyEntitiesAccessory(BaseProductVisibilityResolved $visibilityResolved)
    {
        parent::assertProductIdentifyEntitiesAccessory($visibilityResolved);
        $this->assertEquals($this->accountGroup, $visibilityResolved->getAccountGroup());
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility');
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForVisibilityResolved()
    {
        return $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
    }

    /**
     * @return ProductVisibilityResolved
     */
    protected function getVisibilityResolved()
    {
        $entityManager = $this->getManagerForVisibilityResolved();
        $entity = $entityManager
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->findByPrimaryKey($this->accountGroup, $this->product, $this->website);

        if ($entity) {
            $entityManager->refresh($entity);
        }

        return $entity;
    }

    /**
     * @return ProductVisibility
     */
    protected function getVisibility()
    {
        return $this->getManagerForVisibility()
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findOneBy(
                ['website' => $this->website, 'product' => $this->product, 'accountGroup' => $this->accountGroup]
            );
    }

    /**
     * @inheritdoc
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility'
        );
    }

    /**
     * @return AccountGroupProductRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder()
    {
        $container = $this->client->getContainer();

        $builder = new AccountGroupProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $builder->setCacheClass(
            $container->getParameter('orob2b_account.entity.account_group_product_visibility_resolved.class')
        );

        return $builder;
    }
}
