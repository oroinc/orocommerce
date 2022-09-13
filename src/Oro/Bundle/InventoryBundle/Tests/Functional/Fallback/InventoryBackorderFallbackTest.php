<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Fallback;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityBundle\Tests\Functional\Helper\FallbackTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class InventoryBackorderFallbackTest extends WebTestCase
{
    use FallbackTestTrait;

    const VIEW_BACK_ORDER_XPATH =
        "//label[text() = 'Backorders']/following-sibling::div/div[contains(@class,  'control-label')]";

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadCategoryProductData::class]);
    }

    public function testProductBackOrderView()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $backOrderValue = $crawler->filterXPath(self::VIEW_BACK_ORDER_XPATH)->html();
        $this->assertEquals('No', $backOrderValue);
    }

    public function testProductBackOrderUpdate()
    {
        $newValue = true;
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->setProductBackOrderField($product, $newValue, null);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $value = $crawler->filterXPath(self::VIEW_BACK_ORDER_XPATH)->html();
        $this->assertEquals('Yes', $value);
    }

    /**
     * @param Product $product
     * @param mixed   $ownValue
     * @param mixed   $fallbackValue
     * @return Crawler
     */
    protected function setProductBackOrderField($product, $ownValue, $fallbackValue)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['input_action'] = $crawler->selectButton('Save and Close')->attr('data-action');

        $this->updateFallbackField($form, $ownValue, $fallbackValue, 'oro_product', 'backOrder');

        $this->client->followRedirects(true);

        return $this->client->submit($form);
    }

    public function testCategoryInventoryBackOrder()
    {
        $newCategoryFallbackValue = true;
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $category->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $inventoryBackOrderValue = $form->get('oro_catalog_category[backOrder][scalarValue]')->getValue();
        $this->assertEmpty($inventoryBackOrderValue);

        $form['input_action'] = $crawler->selectButton('Save')->attr('data-action');
        $form['oro_catalog_category[backOrder][useFallback]'] = false;
        $form['oro_catalog_category[backOrder][scalarValue]'] = $newCategoryFallbackValue;
        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $form = $crawler->selectButton('Save')->form();
        $this->assertEquals(
            $newCategoryFallbackValue,
            $form->get('oro_catalog_category[backOrder][scalarValue]')->getValue()
        );
    }
}
