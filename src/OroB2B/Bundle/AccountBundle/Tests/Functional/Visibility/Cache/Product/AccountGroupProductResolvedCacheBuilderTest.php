<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

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
        $this->clearCategoryCache();

        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category->addProduct($this->product);
        $this->registry->getManagerForClass('OroB2BCatalogBundle:Category')->flush();

        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountGroupProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals($category->getId(), $visibilityResolved->getCategory()->getId());
        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_CATEGORY, $visibilityResolved->getSource());
        $this->assertEquals($visibility, $visibilityResolved->getSourceProductVisibility());
        $this->assertEquals(BaseProductVisibilityResolved::VISIBILITY_VISIBLE, $visibilityResolved->getVisibility());
        $this->assertProductIdentifyEntitiesAccessory($visibilityResolved);
    }

    /**
     * @depends testChangeAccountGroupProductVisibilityToCategory
     */
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
}
