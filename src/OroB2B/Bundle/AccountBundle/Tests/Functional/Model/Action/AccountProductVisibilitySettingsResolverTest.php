<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountProductVisibilitySettingsResolverTest extends AbstractVisibilitySettingsResolverTest
{
    /** @var  Account */
    protected $account;

    public function setUp()
    {
        parent::setUp();
        $this->account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
    }

    public function testChangeAccountProductVisibilityToHidden()
    {
        $accountProductVisibility = $this->createAccountProductVisibility();
        $emForProductVisibility = $this->getManagerForAccountProductVisibility();
        $emForProductVisibility->persist($accountProductVisibility);
        $emForProductVisibility->flush();
        $accountProductVisibilityResolved = $this->getAccountProductVisibilityResolved();
        $this->checkStatic(
            $accountProductVisibilityResolved,
            $accountProductVisibility,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );
    }

    /**
     * @depends testChangeAccountProductVisibilityToHidden
     */
    public function testChangeAccountProductVisibilityToVisible()
    {
        $emForProductVisibility = $this
            ->getManagerForAccountProductVisibility();
        /** @var ProductVisibility $accountProductVisibility */
        $accountProductVisibility = $this->getAccountProductVisibility($emForProductVisibility);
        $accountProductVisibility->setVisibility(ProductVisibility::VISIBLE);
        $emForProductVisibility->flush();
        $accountProductVisibilityResolved = $this->getAccountProductVisibilityResolved();
        $this->checkStatic(
            $accountProductVisibilityResolved,
            $accountProductVisibility,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
    }

    /**
     * @depends testChangeAccountProductVisibilityToVisible
     */
    public function testChangeAccountProductVisibilityToCategory()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category->addProduct($this->product);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        $emForProductVisibility = $this
            ->getManagerForAccountProductVisibility();
        $accountProductVisibility = $this->getAccountProductVisibility($emForProductVisibility);
        $accountProductVisibility->setVisibility(AccountProductVisibility::CATEGORY);
        $emForProductVisibility->flush();
        $accountProductVisibilityResolved = $this->getAccountProductVisibilityResolved();
        $this->assertEquals($accountProductVisibilityResolved->getCategory()->getId(), $category->getId());
        $this->assertEquals(
            $accountProductVisibilityResolved->getSource(),
            BaseProductVisibilityResolved::SOURCE_CATEGORY
        );
        $this->assertEquals($accountProductVisibilityResolved->getSourceProductVisibility(), $accountProductVisibility);
        $this->assertEquals(
            $accountProductVisibilityResolved->getVisibility(),
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );
        $this->checkProductIdentifyEntitiesAccessory($accountProductVisibilityResolved);
    }

    /**
     * @depends testChangeAccountProductVisibilityToCategory
     */
    public function testChangeAccountProductVisibilityToAccountGroup()
    {
        $emForProductVisibility = $this
            ->getManagerForAccountProductVisibility();
        $accountProductVisibility = $this->getAccountProductVisibility($emForProductVisibility);
        $emForProductVisibility->remove($accountProductVisibility);
        $this->assertNotNull($this->getAccountProductVisibilityResolved());
        $emForProductVisibility->flush();
        $this->assertNull($this->getAccountProductVisibilityResolved());
    }


    /**
     * @depends testChangeAccountProductVisibilityToAccountGroup
     */
    public function testChangeAccountProductVisibilityToCurrentProductWithoutResolvedFallbackEntity()
    {
        $accountProductVisibility = $this->createAccountProductVisibility();
        $accountProductVisibility->setVisibility(AccountProductVisibility::CURRENT_PRODUCT);
        $emForAccountProductVisibility = $this->getManagerForAccountProductVisibility();
        $emForAccountProductVisibility->persist($accountProductVisibility);
        $emForAccountProductVisibility->flush();
        $accountProductVisibilityResolved = $this->getAccountProductVisibilityResolved();
        $this->assertEquals($accountProductVisibilityResolved->getCategory(), null);
        $this->assertEquals(
            $accountProductVisibilityResolved->getSource(),
            BaseProductVisibilityResolved::SOURCE_STATIC
        );
        $this->assertEquals($accountProductVisibilityResolved->getSourceProductVisibility(), null);
        $this->assertEquals(
            $accountProductVisibilityResolved->getVisibility(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
        $this->checkProductIdentifyEntitiesAccessory($accountProductVisibilityResolved);
    }

    /**
     * @depends testChangeAccountProductVisibilityToCurrentProductWithoutResolvedFallbackEntity
     */
    public function testChangeAccountProductVisibilityToCurrentProduct()
    {
        $productVisibilityValue = BaseProductVisibilityResolved::VISIBILITY_VISIBLE;
        $productVisibilityResolved = new ProductVisibilityResolved($this->website, $this->product);
        $productVisibilityResolved->setVisibility($productVisibilityValue);
        $emForProductVisibility = $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved'
        );
        $emForProductVisibility->persist($productVisibilityResolved);
        $accountProductVisibility = $this->createAccountProductVisibility();
        $accountProductVisibility->setVisibility(AccountProductVisibility::CURRENT_PRODUCT);
        $emForAccountProductVisibility = $this->getManagerForAccountProductVisibility();
        $emForAccountProductVisibility->persist($accountProductVisibility);
        $emForAccountProductVisibility->flush();
        $accountProductVisibilityResolved = $this->getAccountProductVisibilityResolved();
        $this->assertEquals($accountProductVisibilityResolved->getCategory(), null);
        $this->assertEquals(
            $accountProductVisibilityResolved->getSource(),
            BaseProductVisibilityResolved::SOURCE_STATIC
        );
        $this->assertEquals($accountProductVisibilityResolved->getSourceProductVisibility(), null);
        $this->assertEquals(
            $accountProductVisibilityResolved->getVisibility(),
            $productVisibilityValue
        );
        $this->checkProductIdentifyEntitiesAccessory($accountProductVisibilityResolved);
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForAccountProductVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountProductVisibility');
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForAccountProductVisibilityResolved()
    {
        return $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }

    /**
     * @return ProductVisibilityResolved
     */
    protected function getAccountProductVisibilityResolved()
    {
        $emForProductVisibilityResolved = $this->getManagerForAccountProductVisibilityResolved();
        $accountProductVisibilityResolved = $emForProductVisibilityResolved
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findOneBy(['website' => $this->website, 'product' => $this->product, 'account' => $this->account]);

        return $accountProductVisibilityResolved;
    }

    /**
     * @param BaseProductVisibilityResolved|AccountProductVisibilityResolved $visibilityResolved
     */
    protected function checkProductIdentifyEntitiesAccessory(BaseProductVisibilityResolved $visibilityResolved)
    {
        parent::checkProductIdentifyEntitiesAccessory($visibilityResolved);
        $this->assertEquals($this->account, $visibilityResolved->getAccount());
    }

    /**
     * @inheritDoc
     */
    protected function getAdditionalFixtures()
    {
        return ['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'];
    }

    /**
     * @param EntityManager $emForProductVisibility
     * @return ProductVisibility
     */
    protected function getAccountProductVisibility($emForProductVisibility)
    {
        /** @var ProductVisibility $accountProductVisibility */
        $accountProductVisibility = $emForProductVisibility
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product, 'account' => $this->account]);

        return $accountProductVisibility;
    }

    /**
     * @return AccountProductVisibility
     */
    protected function createAccountProductVisibility()
    {
        $accountProductVisibility = new AccountProductVisibility();

        $accountProductVisibility->setWebsite($this->website);
        $accountProductVisibility->setProduct($this->product);
        $accountProductVisibility->setAccount($this->account);
        $accountProductVisibility->setVisibility(ProductVisibility::HIDDEN);

        return $accountProductVisibility;
    }
}
