<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Security;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

/**
 * @group CommunityEdition
 */
class ProductVisibilityTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const VISIBILITY_SYSTEM_CONFIGURATION_PATH = 'oro_visibility.product_visibility';

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $this->setCurrentWebsite('default');
        $this->loadFixtures([
            LoadProductVisibilityData::class,
            LoadCustomerUserData::class
        ]);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    /**
     * @dataProvider visibilityDataProvider
     */
    public function testVisibility(string $configValue, array $expectedData)
    {
        $configManager = self::getConfigManager('global');
        $configManager->set(self::VISIBILITY_SYSTEM_CONFIGURATION_PATH, $configValue);
        $configManager->flush();
        foreach ($expectedData as $productSKU => $resultCode) {
            $product = $this->getReference($productSKU);
            $this->client->request(
                'GET',
                $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
            );
            $response = $this->client->getResponse();

            $this->assertSame($response->getStatusCode(), $resultCode, $productSKU);
        }
    }

    public function visibilityDataProvider(): array
    {
        return [
            'config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => 200,
                    LoadProductData::PRODUCT_2 => 404,
                    LoadProductData::PRODUCT_3 => 404,
                    LoadProductData::PRODUCT_4 => 404,
                    LoadProductData::PRODUCT_6 => 200,
                    LoadProductData::PRODUCT_7 => 200,
                    LoadProductData::PRODUCT_8 => 200,
                ],
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'expectedData' => [
                    LoadProductData::PRODUCT_6 => 404,
                    LoadProductData::PRODUCT_7 => 200,
                ],
            ],
        ];
    }
}
