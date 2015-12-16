<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

abstract class AbstractCacheBuilderTest extends WebTestCase
{
    /** @var  Website */
    protected $website;

    /** @var  Product */
    protected $product;

    /** @var  Registry */
    protected $registry;

    /** @var  AccountGroup */
    protected $accountGroup;

    /** @var  Account */
    protected $account;

    protected function setUp()
    {
        $this->initClient();
        $this->getContainer()->get('doctrine')->getManager()->clear();
        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
        ]);
        $this->registry = $this->client->getContainer()->get('doctrine');
        $this->website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->accountGroup = $this->getReference(LoadGroups::GROUP1);
        $this->account = $this->getReference('account.level_1');
    }

    /**
     * @inheritDoc
     */
    protected function getAdditionalFixtures()
    {
        return [];
    }

    /**
     * @param BaseProductVisibilityResolved $productVisibilityResolved
     * @param VisibilityInterface $productVisibility
     * @param integer $expectedVisibility
     */
    protected function assertStatic(
        BaseProductVisibilityResolved $productVisibilityResolved,
        VisibilityInterface $productVisibility,
        $expectedVisibility
    ) {
        $this->assertNotNull($productVisibilityResolved);
        $this->assertNull($productVisibilityResolved->getCategory());
        $this->assertEquals($this->product, $productVisibilityResolved->getProduct());
        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_STATIC, $productVisibilityResolved->getSource());
        $this->assertEquals($productVisibility, $productVisibilityResolved->getSourceProductVisibility());
        $this->assertEquals($expectedVisibility, $productVisibilityResolved->getVisibility());
        $this->assertProductIdentifyEntitiesAccessory($productVisibilityResolved);
    }

    /**
     * @param BaseProductVisibilityResolved $visibilityResolved
     */
    protected function assertProductIdentifyEntitiesAccessory(BaseProductVisibilityResolved $visibilityResolved)
    {
        $this->assertEquals($this->website, $visibilityResolved->getWebsite());
        $this->assertEquals($this->product, $visibilityResolved->getProduct());
    }
}
