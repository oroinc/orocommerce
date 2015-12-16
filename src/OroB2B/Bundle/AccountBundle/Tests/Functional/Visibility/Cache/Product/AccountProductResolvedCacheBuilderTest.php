<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

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
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category->addProduct($this->product);
        $this->registry->getManagerForClass('OroB2BCatalogBundle:Category')->flush();

        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals($visibility, $visibilityResolved->getSourceProductVisibility());
        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_CATEGORY, $visibilityResolved->getSource());
        $this->assertEquals($category->getId(), $visibilityResolved->getCategoryId());
        $this->assertEquals(BaseProductVisibilityResolved::VISIBILITY_HIDDEN, $visibilityResolved->getVisibility());
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

        // assert account visibility fallback to product
        $visibility = $this->getVisibility();
        $visibility->setVisibility(AccountProductVisibility::CURRENT_PRODUCT);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals($visibility, $visibilityResolved->getSourceProductVisibility());
        $this->assertNull($visibilityResolved->getCategoryId());
        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_STATIC, $visibilityResolved->getSource());
        $this->assertEquals(BaseProductVisibilityResolved::VISIBILITY_HIDDEN, $visibilityResolved->getVisibility());
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
        $this->assertNull($visibilityResolved->getCategoryId());
        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_STATIC, $visibilityResolved->getSource());
        $this->assertEquals(BaseProductVisibilityResolved::VISIBILITY_VISIBLE, $visibilityResolved->getVisibility());
        $this->assertProductIdentifyEntitiesAccessory($visibilityResolved);
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
     * @return null|ProductVisibility
     */
    protected function getVisibility()
    {
        return $this->getManagerForVisibility()
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product, 'account' => $this->account]);
    }

    /**
     * @return null|ProductVisibilityResolved
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
}
