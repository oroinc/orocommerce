<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\FormHandler;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;

/**
 * @dbIsolation
 */
class FormHandlerTest extends WebTestCase
{
    const TEST_SKU = 'SKU-001';
    const UPDATED_SKU = 'SKU-001-updated';
    const FIRST_DUPLICATED_SKU = 'SKU-001-updated-1';
    const SECOND_DUPLICATED_SKU = 'SKU-001-updated-2';

    const STATUS = 'Disabled';
    const UPDATED_STATUS = 'Enabled';

    const INVENTORY_STATUS = 'In Stock';
    const UPDATED_INVENTORY_STATUS = 'Out of Stock';

    const FIRST_UNIT_CODE = 'item';
    const FIRST_UNIT_FULL_NAME = 'item';
    const FIRST_UNIT_PRECISION = '5';

    const SECOND_UNIT_CODE = 'kg';
    const SECOND_UNIT_FULL_NAME = 'kilogram';
    const SECOND_UNIT_PRECISION = '1';

    const DEFAULT_NAME = 'default name';
    const DEFAULT_NAME_ALTERED = 'altered default name';
    const DEFAULT_DESCRIPTION = 'default description';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
        ]);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_product[sku]'] = self::TEST_SKU;
        $form['orob2b_product[owner]'] = $this->getBusinessUnitId();

        $form['orob2b_product[inventoryStatus]'] = Product::INVENTORY_STATUS_IN_STOCK;
        $form['orob2b_product[status]'] = Product::STATUS_DISABLED;
        $form['orob2b_product[names][values][default]'] = self::DEFAULT_NAME;
        $form['orob2b_product[descriptions][values][default]'] = self::DEFAULT_DESCRIPTION;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved', $html);
        $this->assertContains(self::TEST_SKU, $html);
        $this->assertContains(self::INVENTORY_STATUS, $html);
        $this->assertContains(self::STATUS, $html);

        $product = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:Product')->findOneBySku(self::TEST_SKU);

        $resolvedVisibility = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findOneBy(
                [
                    'product' => $product,
                    'website' => $this->getDefaultWebsite()
                ]
            );
        $this->assertNull($resolvedVisibility);
        $visibility = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(
                [
                    'product' => $product,
                    'website' => $this->getDefaultWebsite()
                ]
            );
        $this->assertNotNull($visibility);
        $this->assertSame(ProductVisibility::CONFIG, $visibility->getVisibility());
    }

    public function testUpdate()
    {
        $productVisibilityManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:Visibility\ProductVisibility');
        $product = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product')
            ->findOneBySku(self::TEST_SKU);
        $visibility = $productVisibilityManager->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(
                [
                    'product' => $product,
                    'website' => $this->getDefaultWebsite()
                ]
            );
        $productVisibilityManager->remove($visibility);
        $productVisibilityManager->flush();

        $this->assertNotNull($product);
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $product->getId()]));
        $form = $crawler->selectButton('Save and Close')->form();
        $category = $this->getReference('category_1');
        $form['orob2b_product[category]'] = $category->getId();

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $resolvedVisibility = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findOneBy(
                [
                    'product' => $product,
                    'website' => $this->getDefaultWebsite()
                ]
            );
        $this->assertNotNull($resolvedVisibility);
        $this->assertSame(ProductVisibilityResolved::VISIBILITY_HIDDEN, $resolvedVisibility->getVisibility());
        $visibility = $productVisibilityManager->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(
                [
                    'product' => $product,
                    'website' => $this->getDefaultWebsite()
                ]
            );
        $this->assertNull($visibility);
    }

    /**
     * @return int
     */
    protected function getBusinessUnitId()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser()->getOwner()->getId();
    }

    /**
     * @return \OroB2B\Bundle\WebsiteBundle\Entity\Website
     */
    protected function getDefaultWebsite()
    {
        $className = $this->getContainer()->getParameter('orob2b_website.website.class');
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroB2BWebsiteBundle:Website');

        return $repository->getDefaultWebsite();
    }
}
