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
    private bool $bcValue;

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
        $this->bcValue = $this->getConfigManager()->get('oro_product.display_simple_variations_hide_completely_bc');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $currValue = $this->getConfigManager()->get('oro_product.display_simple_variations');
        $currBcValue = $this->getConfigManager()->get('oro_product.display_simple_variations_hide_completely_bc');

        if ($this->displayValue !== $currValue || $this->bcValue !== $currBcValue) {
            $this->setConfigValue($this->displayValue, $this->bcValue);
        }
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testVariantView(string $productReference, int $expectedCode)
    {
        $this->setConfigValue('hide_completely', true);

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
    public function testVariantViewWhenAllowed(string $displayValue, bool $bcValue)
    {
        $this->setConfigValue($displayValue, $bcValue);

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
        yield ['everywhere', true];
        yield ['hide_catalog', true];
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

    private function setConfigValue(string $value, bool $bcValue): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set('oro_product.display_simple_variations', $value);
        $configManager->set('oro_product.display_simple_variations_hide_completely_bc', $bcValue);
        $configManager->flush();
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->getContainer()->get('oro_config.global');
    }
}
