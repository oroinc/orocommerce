<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Security;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

/**
 * @group CommunityEdition
 * @dbIsolation
 */
class ProductVisibilityTest extends WebTestCase
{
    const VISIBILITY_SYSTEM_CONFIGURATION_PATH = 'oro_visibility.product_visibility';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );
        $this->loadFixtures(
            [
                LoadProductVisibilityData::class,
                LoadAccountUserData::class,
            ]
        );
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    /**
     * @dataProvider visibilityDataProvider
     *
     * @param string $configValue
     * @param array $expectedData
     */
    public function testVisibility($configValue, $expectedData)
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );
        $configManager = $this->getClientInstance()->getContainer()->get('oro_config.global');
        $configManager->set(self::VISIBILITY_SYSTEM_CONFIGURATION_PATH, $configValue);
        $configManager->flush();
        foreach ($expectedData as $productSKU => $resultCode) {
            $product = $this->getReference($productSKU);
            $this->client->request(
                'GET',
                $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()]),
                [],
                [],
                $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
            );
            $response = $this->client->getResponse();
            $this->assertSame($response->getStatusCode(), $resultCode, $productSKU);
        }
    }

    /**
     * @return array
     */
    public function visibilityDataProvider()
    {
        return [
            'config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => 200,
                    LoadProductData::PRODUCT_2 => 403,
                    LoadProductData::PRODUCT_3 => 403,
                    LoadProductData::PRODUCT_4 => 403,
                    LoadProductData::PRODUCT_5 => 200,
                    LoadProductData::PRODUCT_6 => 200,
                    LoadProductData::PRODUCT_7 => 200,
                ],
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'expectedData' => [
                    LoadProductData::PRODUCT_6 => 403,
                    LoadProductData::PRODUCT_7 => 200,
                ],
            ],
        ];
    }

    protected function tearDown()
    {
        $configManager = $this->getClientInstance()->getContainer()->get('oro_config.global');
        $configManager->set(self::VISIBILITY_SYSTEM_CONFIGURATION_PATH, ProductVisibility::VISIBLE);
        $configManager->flush();
    }
}
