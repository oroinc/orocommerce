<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsForCheckoutsData;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CheckoutLineItemsManagerTest extends WebTestCase
{
    private CheckoutLineItemsManager $checkoutLineItemsManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $container = self::getContainer();
        $this->checkoutLineItemsManager = $container->get('oro_checkout.data_provider.manager.checkout_line_items');
        $this->loadFixtures([
            LoadShoppingListsForCheckoutsData::class,
        ]);
    }

    /** @dataProvider checkoutDataProvider */
    public function testGetData(string $checkout, array $expectedResult): void
    {
        $checkout = $this->getReference($checkout);
        $data = $this->checkoutLineItemsManager->getData($checkout);
        $data->forAll(
            fn ($key, $item) => self::assertInstanceOf(OrderLineItem::class, $item)
        );
        self::assertEquals($this->getOrderLineItems($expectedResult), $data);
    }

    public function checkoutDataProvider(): array
    {
        return [
            'checkout 1' => [
                'checkout' => LoadShoppingListsForCheckoutsData::CHECKOUT_1,
                'expectedResult' => [
                    [
                        'productSku' => 'PSKU-1',
                        'freeFormProduct' => 'PSKU-2',
                        'fromExternalSource' => 'from_external_source_1',
                        'quantity' => 10.0,
                        'price' => Price::create(100, 'USD'),
                        'productUnitCode' => 'bottle',
                        'shippingMethod' => 'test_shipping_method_1',
                        'shippingMethodType' => 'test_shipping_method_type_1',
                        'shippingEstimateAmount' => 10.00,
                        'comment' => 'test_comment_1',
                        'checksum' => md5('PSKU-1'),
                        'references' => [
                            'productUnit' => 'product_unit.bottle',
                        ],
                    ],
                    [
                        'productSku' => 'PSKU-2',
                        'freeFormProduct' => 'PSKU-1',
                        'fromExternalSource' => 'from_external_source_2',
                        'quantity' => 20.0,
                        'price' => Price::create(200, 'USD'),
                        'productUnitCode' => 'bottle',
                        'shippingMethod' => 'test_shipping_method_2',
                        'shippingMethodType' => 'test_shipping_method_type_2',
                        'shippingEstimateAmount' => 20.00,
                        'comment' => 'test_comment_2',
                        'checksum' => md5('PSKU-2'),
                        'references' => [
                            'productUnit' => 'product_unit.bottle',
                        ],
                    ],
                ],
            ],
            'checkout 2' => [
                'checkout' => LoadShoppingListsForCheckoutsData::CHECKOUT_2,
                'expectedResult' => [
                    [
                        'productSku' => 'PSKU-3',
                        'quantity' => 30.0,
                        'price' => Price::create(300, 'USD'),
                        'productUnitCode' => 'bottle',
                        'references' => [
                            'productUnit' => 'product_unit.bottle',
                        ],
                    ],
                ],
            ],
            'checkout 3' => [
                'checkout' => LoadShoppingListsForCheckoutsData::CHECKOUT_3,
                'expectedResult' => [
                    [
                        'productSku' => 'PSKU-4',
                        'freeFormProduct' => 'PSKU-3',
                        'fromExternalSource' => 'from_external_source_4',
                        'quantity' => 40.0,
                        'price' => Price::create(400, 'USD'),
                        'productUnitCode' => 'bottle',
                        'shippingMethod' => 'test_shipping_method_4',
                        'shippingMethodType' => 'test_shipping_method_type_4',
                        'shippingEstimateAmount' => 40.00,
                        'comment' => 'test_comment_4',
                        'checksum' => md5('PSKU-4'),
                        'references' => [
                            'productUnit' => 'product_unit.bottle',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $lineItemsData
     * @return ArrayCollection<OrderLineItem>
     */
    private function getOrderLineItems(array $lineItemsData): ArrayCollection
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $result = new ArrayCollection();

        foreach ($lineItemsData as $lineItemData) {
            $orderLineItem = new OrderLineItem();
            foreach ($lineItemData as $field => $value) {
                if ($field !== 'references') {
                    $accessor->setValue($orderLineItem, $field, $value);
                } else {
                    foreach ($value as $refKey => $reference) {
                        $accessor->setValue($orderLineItem, $refKey, $this->getReference($reference));
                    }
                }
            }
            $result->add($orderLineItem);
        }

        return $result;
    }
}
