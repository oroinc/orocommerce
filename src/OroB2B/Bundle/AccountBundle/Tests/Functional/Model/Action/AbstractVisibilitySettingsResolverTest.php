<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

abstract class AbstractVisibilitySettingsResolverTest extends WebTestCase
{
    /** @var  Website */
    protected $website;

    /** @var  Product */
    protected $product;

    /** @var  Registry */
    protected $registry;

    public function setUp()
    {
        $this->markTestSkipped('Must be fixed in scope of BB-1550');

        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            array_merge(
                [
                    'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                    'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
                    'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                ],
                $this->getAdditionalFixtures()
            )
        );
        $this->registry = $this->client->getContainer()->get('doctrine');
        $this->website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
    }

    /**
     * @param BaseProductVisibilityResolved $productVisibilityResolved
     * @param VisibilityInterface $productVisibility
     * @param integer $expectedVisibility
     */
    protected function checkStatic(
        BaseProductVisibilityResolved $productVisibilityResolved,
        VisibilityInterface $productVisibility,
        $expectedVisibility
    ) {
        $this->assertNotNull($productVisibilityResolved);
        $this->assertNull($productVisibilityResolved->getCategory());
        $this->assertEquals($productVisibilityResolved->getProduct(), $this->product);
        $this->assertEquals($productVisibilityResolved->getSource(), BaseProductVisibilityResolved::SOURCE_STATIC);
        $this->assertEquals($productVisibilityResolved->getSourceProductVisibility(), $productVisibility);
        $this->assertEquals(
            $productVisibilityResolved->getVisibility(),
            $expectedVisibility
        );
        $this->checkProductIdentifyEntitiesAccessory($productVisibilityResolved);
    }

    /**
     * @param BaseProductVisibilityResolved $visibilityResolved
     */
    protected function checkProductIdentifyEntitiesAccessory(BaseProductVisibilityResolved $visibilityResolved)
    {
        $this->assertEquals($this->website, $visibilityResolved->getWebsite());
        $this->assertEquals($this->product, $visibilityResolved->getProduct());
    }



    /**
     * @return array
     */
    abstract protected function getAdditionalFixtures();
}
