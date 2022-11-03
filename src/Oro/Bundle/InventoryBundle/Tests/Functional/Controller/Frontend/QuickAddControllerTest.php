<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuickAddControllerTest extends WebTestCase
{
    private EntityManagerInterface $emProduct;

    private EntityManagerInterface $emFallback;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                LoadProductUnitPrecisions::class,
                LoadShoppingLists::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
            ]
        );
        $this->emProduct = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(Product::class);
        $this->emFallback = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(
            EntityFieldFallbackValue::class
        );
        $this->translator = self::getContainer()->get('translator');
    }

    /**
     * @param int $quantity
     * @param int $minLimit
     * @param int $maxLimit
     * @param string $errorMessage
     * @param int $errorLimit
     *
     * @dataProvider getQuickAddData
     */
    public function testQuickAddReturnsErrorIfQuantityOutOfBound(
        int $quantity,
        int $minLimit,
        int $maxLimit,
        string $errorMessage,
        int $errorLimit
    ): void {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $this->setProductLimits($product, $minLimit, $maxLimit);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();
        $processor = self::getContainer()->get('oro_shopping_list.processor.quick_add');

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => json_encode([
                        [
                            QuickAddRow::SKU => $product->getSku(),
                            QuickAddRow::QUANTITY => $quantity,
                        ],
                    ]),
                    'component' => $processor->getName(),
                    'additional' => null,
                ],
            ]
        );

        $errorMessage = $this->translator->trans(
            $errorMessage,
            ['%limit%' => $errorLimit, '%sku%' => $product->getSku(), '%product_name%' => $product->getName()],
            'validators'
        );
        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, 200);
        self::assertFalse($responseData['success']);
        self::assertStringContainsString(
            $errorMessage,
            $responseData['collection']['errors'][0]['message']
        );
    }

    public function getQuickAddData(): array
    {
        return [
            [2, 3, 5, 'oro.inventory.quick_add_row.quantity_to_order.min_message', 3],
            [6, 3, 5, 'oro.inventory.quick_add_row.quantity_to_order.max_message', 5],
        ];
    }

    public function testRFQExcludesQuantityLimitValidation(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $quantity = 1;
        $minLimit = 3;
        $maxLimit = 5;
        $this->setProductLimits($product, $minLimit, $maxLimit);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));

        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();
        $processor = self::getContainer()->get('oro_rfp.processor.quick_add');
        $this->client->followRedirects(false);

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => json_encode([
                        [
                            QuickAddRow::SKU => $product->getSku(),
                            QuickAddRow::QUANTITY => $quantity,
                        ],
                    ]),
                    'component' => $processor->getName(),
                    'additional' => null,
                ],
            ]
        );

        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, 200);
        self::assertTrue($responseData['success']);
        self::assertStringContainsString(
            $this->getUrl('oro_rfp_frontend_request_create'),
            $responseData['redirectUrl']
        );
    }

    private function setProductLimits(Product $product, int $minLimit, int $maxLimit): void
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setScalarValue($minLimit);
        $entityFallback2 = new EntityFieldFallbackValue();
        $entityFallback2->setScalarValue($maxLimit);
        $this->emFallback->persist($entityFallback);
        $this->emFallback->persist($entityFallback2);
        $product->setMinimumQuantityToOrder($entityFallback);
        $product->setMaximumQuantityToOrder($entityFallback2);
        $this->emProduct->flush();
        $this->emFallback->flush();
    }
}
