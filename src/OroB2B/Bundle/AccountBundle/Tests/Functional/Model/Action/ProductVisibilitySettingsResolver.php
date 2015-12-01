<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class ProductVisibilitySettingsResolver extends AbstractVisibilitySettingsResolver
{
    public function testChangeProductVisibilityToHidden()
    {
        $productVisibility = new ProductVisibility();

        $productVisibility->setWebsite($this->website);
        $productVisibility->setProduct($this->product);
        $productVisibility->setVisibility(ProductVisibility::HIDDEN);
        $emForProductVisibility = $this->getManagerForProductVisibility();
        $emForProductVisibility->persist($productVisibility);
        $emForProductVisibility->flush();
        $productVisibilityResolved = $this->getProductVisibilityResolved();
        $this->checkStatic(
            $productVisibilityResolved,
            $productVisibility,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );
    }

    /**
     * @depends testChangeProductVisibilityToHidden
     */
    public function testChangeProductVisibilityToVisible()
    {
        $emForProductVisibility = $this
            ->getManagerForProductVisibility();
        /** @var ProductVisibility $productVisibility */
        $productVisibility = $emForProductVisibility
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product]);
        $this->assertNotNull($productVisibility);
        $productVisibility->setVisibility(ProductVisibility::VISIBLE);
        $emForProductVisibility->flush();
        $productVisibilityResolved = $this->getProductVisibilityResolved();
        $this->checkStatic(
            $productVisibilityResolved,
            $productVisibility,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
    }

    /**
     * @depends testChangeProductVisibilityToVisible
     */
    public function testChangeProductVisibilityToConfig()
    {
        $emForProductVisibility = $this
            ->getManagerForProductVisibility();
        /** @var ProductVisibility $productVisibility */
        $productVisibility = $emForProductVisibility
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product]);
        $productVisibility->setVisibility(ProductVisibility::CONFIG);
        $this->assertNotNull($this->getProductVisibilityResolved());
        $emForProductVisibility->flush();
        $this->assertNull($this->getProductVisibilityResolved());
    }

    /**
     * @depends testChangeProductVisibilityToConfig
     */
    public function testChangeProductVisibilityToCategory()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category->addProduct($this->product);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        $emForProductVisibility = $this
            ->getManagerForProductVisibility();
        /** @var ProductVisibility $productVisibility */
        $productVisibility = $emForProductVisibility
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product]);
        $emForProductVisibility->remove($productVisibility);
        $emForProductVisibility->flush();
        $productVisibilityResolved = $this->getProductVisibilityResolved();
        $this->assertEquals($productVisibilityResolved->getCategoryId(), $category->getId());
        $this->assertEquals($productVisibilityResolved->getSource(), BaseProductVisibilityResolved::SOURCE_CATEGORY);
        $this->assertNull($productVisibilityResolved->getSourceProductVisibility());
        $this->assertEquals(
            $productVisibilityResolved->getVisibility(),
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );
        $this->checkProductIdentifyEntitiesAccessory($productVisibilityResolved);
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForProductVisibility()
    {
        return $this->registry->getManagerForClass(
            'OroB2BAccountBundle:Visibility\ProductVisibility'
        );
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForProductVisibilityResolved()
    {
        return $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved'
        );
    }


    /**
     * @return ProductVisibilityResolved
     */
    protected function getProductVisibilityResolved()
    {
        $emForProductVisibilityResolved = $this->getManagerForProductVisibilityResolved();
        $productVisibilityResolved = $emForProductVisibilityResolved
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findOneBy(['website' => $this->website, 'product' => $this->product]);

        return $productVisibilityResolved;
    }

    /**
     * @inheritDoc
     */
    protected function getAdditionalFixtures()
    {
        return [];
    }
}
