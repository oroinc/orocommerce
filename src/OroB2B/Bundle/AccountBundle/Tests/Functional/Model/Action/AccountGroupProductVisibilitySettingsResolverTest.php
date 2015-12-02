<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilitySettingsResolverTest extends AbstractVisibilitySettingsResolverTest
{
    /** @var  AccountGroup */
    protected $accountGroup;

    public function setUp()
    {
        parent::setUp();
        $this->accountGroup = $this->getReference(LoadGroups::GROUP1);
    }

    public function testChangeAccountGroupProductVisibilityToHidden()
    {
        $accountGroupProductVisibility = $this->createAccountGroupProductVisibility();
        $emForProductVisibility = $this->getManagerForAccountGroupProductVisibility();
        $emForProductVisibility->persist($accountGroupProductVisibility);
        $emForProductVisibility->flush();
        $accountGroupProductVisibilityResolved = $this->getAccountGroupProductVisibilityResolved();
        $this->checkStatic(
            $accountGroupProductVisibilityResolved,
            $accountGroupProductVisibility,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );
    }

    /**
     * @depends testChangeAccountGroupProductVisibilityToHidden
     */
    public function testChangeAccountGroupProductVisibilityToVisible()
    {
        $emForProductVisibility = $this->getManagerForAccountGroupProductVisibility();
        /** @var ProductVisibility $accountGroupProductVisibility */
        $accountGroupProductVisibility = $this->getAccountGroupProductVisibility($emForProductVisibility);
        $accountGroupProductVisibility->setVisibility(ProductVisibility::VISIBLE);
        $emForProductVisibility->flush();
        $accountGroupProductVisibilityResolved = $this->getAccountGroupProductVisibilityResolved();
        $this->checkStatic(
            $accountGroupProductVisibilityResolved,
            $accountGroupProductVisibility,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
    }

    /**
     * @depends testChangeAccountGroupProductVisibilityToVisible
     */
    public function testChangeAccountGroupProductVisibilityToCategory()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category->addProduct($this->product);
        $this->getContainer()->get('doctrine')->getManager()->flush();
        $emForProductVisibility = $this
            ->getManagerForAccountGroupProductVisibility();
        $accountGroupProductVisibility = $this->getAccountGroupProductVisibility($emForProductVisibility);
        $accountGroupProductVisibility->setVisibility(AccountGroupProductVisibility::CATEGORY);
        $emForProductVisibility->flush();
        $accountGroupProductVisibilityResolved = $this->getAccountGroupProductVisibilityResolved();
        $this->assertEquals($accountGroupProductVisibilityResolved->getCategoryId(), $category->getId());
        $this->assertEquals(
            $accountGroupProductVisibilityResolved->getSource(),
            BaseProductVisibilityResolved::SOURCE_CATEGORY
        );
        $this->assertEquals(
            $accountGroupProductVisibilityResolved->getSourceProductVisibility(),
            $accountGroupProductVisibility
        );
        $this->assertEquals(
            $accountGroupProductVisibilityResolved->getVisibility(),
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );
        $this->checkProductIdentifyEntitiesAccessory($accountGroupProductVisibilityResolved);
    }

    /**
     * @depends testChangeAccountGroupProductVisibilityToCategory
     */
    public function testChangeAccountGroupProductVisibilityToCurrentProduct()
    {
        $emForProductVisibility = $this->getManagerForAccountGroupProductVisibility();
        $accountGroupProductVisibility = $this->getAccountGroupProductVisibility($emForProductVisibility);
        $emForProductVisibility->remove($accountGroupProductVisibility);
        $this->assertNotNull($this->getAccountGroupProductVisibilityResolved());
        $emForProductVisibility->flush();
        $this->assertNull($this->getAccountGroupProductVisibilityResolved());
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForAccountGroupProductVisibility()
    {
        return $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility');
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForAccountGroupProductVisibilityResolved()
    {
        return $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
    }

    /**
     * @return ProductVisibilityResolved
     */
    protected function getAccountGroupProductVisibilityResolved()
    {
        $emForProductVisibilityResolved = $this->getManagerForAccountGroupProductVisibilityResolved();
        $accountGroupProductVisibilityResolved = $emForProductVisibilityResolved
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->findOneBy(
                ['website' => $this->website, 'product' => $this->product, 'accountGroup' => $this->accountGroup]
            );

        return $accountGroupProductVisibilityResolved;
    }

    /**
     * @param BaseProductVisibilityResolved|AccountGroupProductVisibilityResolved $visibilityResolved
     */
    protected function checkProductIdentifyEntitiesAccessory(BaseProductVisibilityResolved $visibilityResolved)
    {
        parent::checkProductIdentifyEntitiesAccessory($visibilityResolved);
        $this->assertEquals($this->accountGroup, $visibilityResolved->getAccountGroup());
    }

    /**
     * @inheritDoc
     */
    protected function getAdditionalFixtures()
    {
        return ['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups'];
    }

    /**
     * @param EntityManager $emForProductVisibility
     * @return ProductVisibility
     */
    protected function getAccountGroupProductVisibility($emForProductVisibility)
    {
        /** @var ProductVisibility $accountGroupProductVisibility */
        $accountGroupProductVisibility = $emForProductVisibility
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findOneBy(
                ['website' => $this->website, 'product' => $this->product, 'accountGroup' => $this->accountGroup]
            );

        return $accountGroupProductVisibility;
    }

    /**
     * @return AccountGroupProductVisibility
     */
    protected function createAccountGroupProductVisibility()
    {
        $accountGroupProductVisibility = new AccountGroupProductVisibility();

        $accountGroupProductVisibility->setWebsite($this->website);
        $accountGroupProductVisibility->setProduct($this->product);
        $accountGroupProductVisibility->setAccountGroup($this->accountGroup);
        $accountGroupProductVisibility->setVisibility(ProductVisibility::HIDDEN);

        return $accountGroupProductVisibility;
    }
}
