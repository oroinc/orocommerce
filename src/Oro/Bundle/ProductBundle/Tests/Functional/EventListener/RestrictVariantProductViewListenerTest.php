<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductVariants;

/**
 * @dbIsolationPerTest
 */
class RestrictVariantProductViewListenerTest extends FrontendWebTestCase
{
    private string $displayValue;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->setCurrentWebsite('default');
        $this->loadFixtures(
            [
                LoadProductVariants::class,
            ]
        );
        $this->displayValue = $this->getConfigManager()->get('oro_product.display_simple_variations');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $currValue = $this->getConfigManager()->get('oro_product.display_simple_variations');
        if ($this->displayValue !== $currValue) {
            $this->setConfigValue($this->displayValue);
        }
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testVariantView(string $productReference, int $expectedCode)
    {
        $product = $this->getReference($productReference);
        $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
        );

        $response = $this->client->getResponse();
        $this->assertSame($expectedCode, $response->getStatusCode());
    }

    /**
     * @dataProvider allowToViewDisplayValuesDataProvider
     */
    public function testVariantViewWhenAllowed(string $displayValue)
    {
        $this->setConfigValue($displayValue);

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
    }

    public function allowToViewDisplayValuesDataProvider(): \Generator
    {
        yield ['everywhere'];
        yield ['hide_catalog'];
    }

    public function productsDataProvider(): \Generator
    {
        yield 'simple product' => [
            'productReference' => LoadProductData::PRODUCT_6,
            'expectedCode' => 200,
        ];

        yield 'configurable product' => [
            'productReference' => LoadProductData::PRODUCT_8,
            'expectedCode' => 200,
        ];

        yield 'product variant' => [
            'productReference' => LoadProductData::PRODUCT_1,
            'expectedCode' => 404,
        ];
    }

    private function setConfigValue(string $value): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set('oro_product.display_simple_variations', $value);
        $configManager->flush();
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->getContainer()->get('oro_config.global');
    }
}
