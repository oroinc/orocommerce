<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ControllerFrontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class QuickAddControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadProductData::class,
        ]);
    }

    public function testQuickAddValidationWithVariousErrors(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();

        // Test various validation errors:
        // 1. Non-existent SKU
        // 2. Non-existent product unit
        // 3. Empty quantity
        // 4. Zero quantity
        // 5. Quantity too high (but this will fail on unit validation first)
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => json_encode([
                        [
                            QuickAddRow::SKU => 'NONEXISTENT_SKU',
                            QuickAddRow::QUANTITY => 1,
                            QuickAddRow::UNIT => 'kg',
                        ],
                        [
                            QuickAddRow::SKU => $product->getSku(),
                            QuickAddRow::QUANTITY => 1,
                            QuickAddRow::UNIT => 'NONEXISTENT_UNIT',
                        ],
                        [
                            QuickAddRow::SKU => $product->getSku(),
                            QuickAddRow::QUANTITY => '',
                            QuickAddRow::UNIT => 'item',
                        ],
                        [
                            QuickAddRow::SKU => $product->getSku(),
                            QuickAddRow::QUANTITY => 0,
                            QuickAddRow::UNIT => 'item',
                        ],
                        [
                            QuickAddRow::SKU => $product->getSku(),
                            QuickAddRow::QUANTITY => 99999999999999999999999,
                            QuickAddRow::UNIT => 'item',
                        ],
                    ], JSON_THROW_ON_ERROR),
                    'component' => 'oro_shopping_list_quick_add_processor',
                    'additional' => null,
                ],
            ]
        );

        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, 200);

        // Check that we have validation errors
        self::assertArrayHasKey('collection', $responseData);
        self::assertArrayHasKey('items', $responseData['collection']);
        self::assertCount(5, $responseData['collection']['items']);

        // Check first item - non-existent SKU
        $firstItem = $responseData['collection']['items'][0];
        self::assertArrayHasKey('errors', $firstItem);
        self::assertNotEmpty($firstItem['errors']);
        $firstItemErrors = array_column($firstItem['errors'], 'message');
        self::assertContains('Item number cannot be found.', $firstItemErrors);

        // Check second item - non-existent unit
        $secondItem = $responseData['collection']['items'][1];
        self::assertArrayHasKey('errors', $secondItem);
        self::assertNotEmpty($secondItem['errors']);
        $secondItemErrors = array_column($secondItem['errors'], 'message');
        self::assertContains('Unit \'NONEXISTENT_UNIT\' doesn\'t exist for product product-1.', $secondItemErrors);

        // Check third item - empty quantity
        $thirdItem = $responseData['collection']['items'][2];
        self::assertArrayHasKey('errors', $thirdItem);
        self::assertNotEmpty($thirdItem['errors']);
        $thirdItemErrors = array_column($thirdItem['errors'], 'message');
        self::assertContains('Quantity should be greater than 0.', $thirdItemErrors);

        // Check fourth item - zero quantity
        $fourthItem = $responseData['collection']['items'][3];
        self::assertArrayHasKey('errors', $fourthItem);
        self::assertNotEmpty($fourthItem['errors']);
        $fourthItemErrors = array_column($fourthItem['errors'], 'message');
        self::assertContains('Quantity should be greater than 0.', $fourthItemErrors);

        // Check fifth item - quantity too high
        $fifthItem = $responseData['collection']['items'][4];
        self::assertArrayHasKey('errors', $fifthItem);
        self::assertNotEmpty($fifthItem['errors']);
        $fifthItemErrors = array_column($fifthItem['errors'], 'message');
        self::assertContains('Quantity should be less than 999999999.', $fifthItemErrors);
    }
}
