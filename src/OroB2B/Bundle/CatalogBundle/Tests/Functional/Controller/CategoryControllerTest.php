<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\ProductCategory;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class CategoryControllerTest extends WebTestCase
{
    const DEFAULT_CATEGORY_TITLE = 'Category Title';
    const UPDATED_DEFAULT_CATEGORY_TITLE = 'Updated Category Title';
    const DEFAULT_SUBCATEGORY_TITLE = 'Subcategory Title';
    const UPDATED_DEFAULT_SUBCATEGORY_TITLE = 'Updated Subcategory Title';

    /**
     * @var Locale[]
     */
    protected $locales;

    /**
     * @var Category
     */
    protected $masterCatalog;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->loadFixtures([
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadLocaleData',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadProductData'
        ]);
        $this->locales       = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BWebsiteBundle:Locale')
            ->findAll();
        $this->masterCatalog = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_catalog_category_index'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals("Categories", $crawler->filter('h1.oro-subtitle')->html());
        $this->assertContains(
            "Please select a category on the left or create new one.",
            $crawler->filter('.content .text-center')->html()
        );
    }

    /**
     * @return int
     */
    public function testCreateCategory()
    {
        $this->getContainer()->get('doctrine')->getRepository('OroB2BCatalogBundle:Category')->getMasterCatalogRoot();

        return $this->assertCreate(self::DEFAULT_CATEGORY_TITLE, $this->masterCatalog->getId());
    }

    /**
     * @depends testCreateCategory
     *
     * @param int $id
     *
     * @return int
     */
    public function testEditCategory($id)
    {
        return $this->assertEdit(self::DEFAULT_CATEGORY_TITLE, self::UPDATED_DEFAULT_CATEGORY_TITLE, $id);
    }

    /**
     * @depends testCreateCategory
     *
     * @param int $id
     *
     * @return int
     */
    public function testCreateSubCategory($id)
    {
        return $this->assertCreate(self::DEFAULT_SUBCATEGORY_TITLE, $id);
    }

    /**
     * @depends testCreateSubCategory
     *
     * @param int $id
     *
     * @return int
     */
    public function testEditSubCategory($id)
    {
        return $this->assertEdit(self::DEFAULT_SUBCATEGORY_TITLE, self::UPDATED_DEFAULT_SUBCATEGORY_TITLE, $id);
    }

    /**
     * @depends testEditCategory
     *
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request('DELETE', $this->getUrl('orob2b_api_delete_category', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_catalog_category_update', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }


    public function testDeleteRoot()
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_category', ['id' => $this->masterCatalog->getId()])
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 500);
    }

    /**
     * @param string $title
     * @param int    $parentId
     *
     * @return int
     */
    protected function assertCreate($title, $parentId)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_catalog_category_create', ['id' => $parentId])
        );

        /** @var Form $form */
        $form                                                     = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_catalog_category[titles][values][default]'] = $title;
        $appendProducts                                           = $this->getProductBySku(LoadProductData::TEST_PRODUCT_01)->getId() . ', '
            . $this->getProductBySku(LoadProductData::TEST_PRODUCT_02)->getId();
        $form['orob2b_catalog_category[appendProducts]']          = $appendProducts;
        $form->setValues(['input_action' => 'save_and_stay']);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Category has been saved", $crawler->html());

        return $this->getCategoryIdByUri($this->client->getRequest()->getRequestUri());
    }

    /**
     * @param string $title
     * @param string $newTitle
     * @param int    $id
     *
     * @return int
     */
    protected function assertEdit($title, $newTitle, $id)
    {
        $crawler    = $this->client->request('GET', $this->getUrl('orob2b_catalog_category_update', ['id' => $id]));
        $form       = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getValues();
        $this->assertEquals($title, $formValues['orob2b_catalog_category[titles][values][default]']);
        $testProduct1 = $this->getProductBySku(LoadProductData::TEST_PRODUCT_01);
        $testProduct2 = $this->getProductBySku(LoadProductData::TEST_PRODUCT_02);
        $testProduct3 = $this->getProductBySku(LoadProductData::TEST_PRODUCT_03);
        $this->assertEquals(
            $title,
            $this->getProductCategoryByProduct($testProduct1)->getCategory()->getDefaultTitle()
        );
        $this->assertEquals(
            $title,
            $this->getProductCategoryByProduct($testProduct2)->getCategory()->getDefaultTitle()
        );

        $form['orob2b_catalog_category[titles][values][default]'] = $newTitle;

        $form['orob2b_catalog_category[appendProducts]'] = $testProduct3->getId();
        $form['orob2b_catalog_category[removeProducts]'] = $testProduct1->getId();

        $form->setValues(['input_action' => 'save_and_stay']);

        foreach ($this->locales as $locale) {
            $form['orob2b_catalog_category[titles][values][locales][' . $locale->getId() . '][value]']
                = $locale->getCode() . $newTitle;
        }

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Category has been saved", $crawler->html());

        $formValues = $form->getValues();
        $this->assertEquals($newTitle, $formValues['orob2b_catalog_category[titles][values][default]']);

        foreach ($this->locales as $locale) {
            $this->assertEquals(
                $locale->getCode() . $newTitle,
                $formValues['orob2b_catalog_category[titles][values][locales][' . $locale->getId() . '][value]']
            );
        }

        $this->assertNull(
            $this->getProductCategoryByProduct($testProduct1)
        );
        $this->assertEquals(
            $newTitle,
            $this->getProductCategoryByProduct($testProduct2)->getCategory()->getDefaultTitle()
        );
        $this->assertEquals(
            $newTitle,
            $this->getProductCategoryByProduct($testProduct3)->getCategory()->getDefaultTitle()
        );

        return $id;
    }

    /**
     * @param string $uri
     *
     * @return int
     */
    protected function getCategoryIdByUri($uri)
    {
        $router     = $this->getContainer()->get('router');
        $parameters = $router->match($uri);

        $this->assertArrayHasKey('id', $parameters);

        return $parameters['id'];
    }

    /**
     * @param string $sku
     *
     * @return Product
     */
    protected function getProductBySku($sku)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:Product')
            ->findOneBy(['sku' => $sku]);
    }

    /**
     * @param Product $product
     *
     * @return null|ProductCategory
     */
    protected function getProductCategoryByProduct(Product $product)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BCatalogBundle:ProductCategory')
            ->findOneByProduct($product);
    }
}
