<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Security;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductVisibilityTest extends WebTestCase
{
    const VISIBILITY_SYSTEM_CONFIGURATION_PATH = 'oro_b2b_account.product_visibility';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
        ]);

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
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $configManager = $this->getClientInstance()->getContainer()->get('oro_config.global');
        $configManager->set(self::VISIBILITY_SYSTEM_CONFIGURATION_PATH, $configValue);
        $configManager->flush();
        foreach ($expectedData as $productSKU => $resultCode) {
            $product = $this->getReference($productSKU);
            $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product', $product);
            $this->client->request(
                'GET',
                $this->getUrl('orob2b_product_frontend_product_view', ['id' => $product->getId()])
            );
            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, $resultCode);
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
                    LoadProductData::PRODUCT_2 => 200,
                    LoadProductData::PRODUCT_3 => 200,
                    LoadProductData::PRODUCT_4 => 403,
                    LoadProductData::PRODUCT_5 => 200,
                ]
            ],
            'config hidden' => [
                'configValue' => ProductVisibility::HIDDEN,
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => 403,
                    LoadProductData::PRODUCT_2 => 200,
                    LoadProductData::PRODUCT_3 => 200,
                    LoadProductData::PRODUCT_4 => 403,
                    LoadProductData::PRODUCT_5 => 403,
                ]
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
