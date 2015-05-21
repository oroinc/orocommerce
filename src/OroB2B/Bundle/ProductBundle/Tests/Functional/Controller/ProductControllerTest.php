<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const TEST_SKU = 'SKU-001';
    const UPDATED_SKU = 'SKU-001-updated';

    const CATEGORY_NAME = 'Test First Level';
    const UPDATED_CATEGORY_NAME = 'Test Third Level 2';

    const FIRST_UNIT_CODE = 'item';
    const FIRST_UNIT_FULL_NAME = 'item';
    const FIRST_UNIT_PRECISION = '5';
    const SECOND_UNIT_CODE = 'kg';
    const SECOND_UNIT_FULL_NAME = 'kilogram';
    const SECOND_UNIT_PRECISION = '1';

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData']);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("products-grid", $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_product_form[sku]'] = self::TEST_SKU;
        $form['orob2b_product_form[owner]'] = $this->getBusinessUnitId();

        $category = $this->getCategoryByDefaultTitle(self::CATEGORY_NAME);
        $form['orob2b_product_form[category]'] = $category->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Product has been saved", $crawler->html());
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'products-grid',
            ['products-grid[_filter][sku][value]' => self::TEST_SKU]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $this->assertEquals(self::TEST_SKU, $result['sku']);
        $this->assertEquals(self::CATEGORY_NAME, $result['category']);

        $id = $result['id'];
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $id]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_product_form' => [
                '_token' => $form['orob2b_product_form[_token]']->getValue(),
                'sku' => self::UPDATED_SKU,
                'owner' => $this->getBusinessUnitId(),
                'category' => $this->getCategoryByDefaultTitle(self::UPDATED_CATEGORY_NAME),
                'unitPrecisions' => [
                    ['unit' => self::FIRST_UNIT_CODE, 'precision' => self::FIRST_UNIT_PRECISION],
                    ['unit' => self::SECOND_UNIT_CODE, 'precision' => self::SECOND_UNIT_PRECISION],
                ]
            ]
        ];

        $this->client->followRedirects(true);

        $result = $this->client->getResponse();
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check product unit precisions
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $id]));

        $this->assertEquals(
            self::FIRST_UNIT_FULL_NAME,
            $crawler->filter('select[name="orob2b_product_form[unitPrecisions][0][unit]"] :selected')->html()
        );
        $this->assertEquals(
            self::FIRST_UNIT_PRECISION,
            $crawler->filter('input[name="orob2b_product_form[unitPrecisions][0][precision]"]')
                ->extract('value')[0]
        );
        $this->assertEquals(
            self::SECOND_UNIT_FULL_NAME,
            $crawler->filter('select[name="orob2b_product_form[unitPrecisions][1][unit]"] :selected')->html()
        );
        $this->assertEquals(
            self::SECOND_UNIT_PRECISION,
            $crawler->filter('input[name="orob2b_product_form[unitPrecisions][1][precision]"]')
                ->extract('value')[0]
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     * @return int
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::UPDATED_SKU . ' - Products - Product management', $crawler->html());
        $product = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:Product')
            ->findOneBy(['sku' => self::UPDATED_SKU]);
        $this->assertNotEmpty($product->getCategory());

        $productUnitPrecision = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:ProductUnitPrecision')
            ->findOneBy(['product' => $id, 'unit' => self::FIRST_UNIT_CODE]);
        $this->assertEquals(self::FIRST_UNIT_PRECISION, $productUnitPrecision->getPrecision());

        $productUnitPrecision = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:ProductUnitPrecision')
            ->findOneBy(['product' => $id, 'unit' => self::SECOND_UNIT_CODE]);
        $this->assertEquals(self::SECOND_UNIT_PRECISION, $productUnitPrecision->getPrecision());

        $this->assertEquals(self::UPDATED_CATEGORY_NAME, $product->getCategory()->getDefaultTitle());

        return $id;
    }

    /**
     * @depends testView
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request('DELETE', $this->getUrl('orob2b_api_delete_product', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @param string $title
     * @return Category|null
     */
    protected function getCategoryByDefaultTitle($title)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByDefaultTitle($title);
    }

    /**
     * @return int
     */
    protected function getBusinessUnitId()
    {
        return $this->getContainer()->get('security.context')->getToken()->getUser()->getOwner()->getId();
    }
}
