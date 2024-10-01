<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Fallback;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityBundle\Tests\Functional\Helper\FallbackTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class InventoryBackorderFallbackTest extends WebTestCase
{
    use FallbackTestTrait;

    private const VIEW_BACK_ORDER_XPATH =
        "//label[text() = 'Backorders']/following-sibling::div/div[contains(@class,  'control-label')]";

    #[\Override]
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

    private function setProductBackOrderField(
        Product $product,
        mixed $ownValue,
        mixed $fallbackValue
    ): Crawler {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['input_action'] = $crawler->selectButton('Save and Close')->attr('data-action');

        $this->updateFallbackField($form, $ownValue, $fallbackValue, 'oro_product', 'backOrder');

        $this->client->followRedirects();

        return $this->client->submit($form);
    }

    public function testCategoryInventoryBackOrder()
    {
        // Get form
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $category->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();

        // Ensure that the tested field has a value different from the value that it will be changed to
        $inventoryBackOrderValue = $form->get('oro_catalog_category[backOrder][scalarValue]')->getValue();
        $this->assertEquals('0', $inventoryBackOrderValue);

        // Fill the form
        $this->updateFallbackField($form, '1', null, 'oro_catalog_category', 'backOrder');

        // Submit form
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);

        // Assert result
        $form = $crawler->selectButton('Save')->form();
        $actualScalarValue = $form->get('oro_catalog_category[backOrder][scalarValue]')->getValue();
        $this->assertEquals('1', $actualScalarValue);

        // Ensure that the flash message was fired
        $this->assertStringContainsString('Category has been saved', $this->client->getResponse()->getContent());
    }
}
