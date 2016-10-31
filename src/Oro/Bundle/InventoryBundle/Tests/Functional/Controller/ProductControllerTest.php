<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\EntityBundle\Tests\Functional\Helper\FallbackTestTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    use FallbackTestTrait;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductData::class]);
    }

    public function testAddQuantityToOrder()
    {
        $productId = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $this->updateProduct($productId, '123', '321', null, null);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $productId]));
        $this->assertEquals('123', $this->getMinValue($crawler));
        $this->assertEquals('321', $this->getMaxValue($crawler));
    }

    public function testFallbackQuantity()
    {
        $productId = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $productId]));
        $originalMinValue = $this->getMinValue($crawler);
        $originalMaxValue = $this->getMaxValue($crawler);
        $this->updateProduct($productId, null, null, 'systemConfig', 'category');

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $productId]));
        $this->assertNotEquals($originalMinValue, $this->getMinValue($crawler));
        $this->assertNotEquals($originalMaxValue, $this->getMaxValue($crawler));
    }

    protected function getMinValue(Crawler $crawler)
    {
        return $crawler->filterXPath(
            '//label[text()=\'Minimum Quantity To Order\']/following-sibling::div/div'
        )->html();
    }

    protected function getMaxValue(Crawler $crawler)
    {
        return $crawler->filterXPath(
            '//label[text()=\'Maximum Quantity To Order\']/following-sibling::div/div'
        )->html();
    }

    /**
     * @param integer $productId
     * @param mixed $minScalar
     * @param mixed $maxScalar
     * @param string $minFallback
     * @param string $maxFallback
     */
    protected function updateProduct($productId, $minScalar, $maxScalar, $minFallback, $maxFallback)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $productId]));
        $form = $crawler->selectButton('Save and Close')->form();
        $this->updateFallbackField($form, $minScalar, $minFallback, 'oro_product', 'minimumQuantityToOrder');
        $this->updateFallbackField($form, $maxScalar, $maxFallback, 'oro_product', 'maximumQuantityToOrder');

        $this->client->submit($form);
        $this->client->followRedirects();
    }
}
