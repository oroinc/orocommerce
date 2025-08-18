<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductVariants;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

/**
 * @dbIsolationPerTest
 */
class RestrictVariantProductViewListenerTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?string $initialDisplayValue;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->setCurrentWebsite('default');
        $this->loadFixtures(
            [
                LoadProductVariants::class,
            ]
        );
        $this->initialDisplayValue = self::getConfigManager()->get('oro_product.display_simple_variations');
        $this->reindexProducts();
    }

    /**
     * Re-index products as other tests may have changed the index.
     */
    private function reindexProducts(): void
    {
        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        if ($configManager->get('oro_product.display_simple_variations') !== $this->initialDisplayValue) {
            $configManager->set('oro_product.display_simple_variations', $this->initialDisplayValue);
            $configManager->flush();
        }

        parent::tearDown();
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
        $configManager = self::getConfigManager();
        $configManager->set('oro_product.display_simple_variations', $displayValue);
        $configManager->flush();

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
}
