<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\DraftSession;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemDraftData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemDraftDataWithTaxesAndDiscounts;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Environment;

/**
 * @dbIsolationPerTest
 */
final class OrderLineItemDraftCalculateTaxesAndDiscountsControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private DraftSessionOrmFilterManager $draftSessionOrmFilterManager;

    private bool $isTaxationEnabled = true;
    private string $taxationUseAsBaseOption = 'origin';
    private bool $isPromotionEnabled = true;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->draftSessionOrmFilterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');
        $this->draftSessionOrmFilterManager->disable();

        $this->loadFixtures([
            LoadOrderLineItemDraftDataWithTaxesAndDiscounts::class,
        ]);

        $this->isTaxationEnabled = self::getConfigManager()->get('oro_tax.tax_enable');
        $this->taxationUseAsBaseOption = self::getConfigManager()->get('oro_tax.use_as_base_by_default');
        $this->isPromotionEnabled = self::getConfigManager()->get('oro_promotion.feature_enabled');

        self::getConfigManager()->set('oro_tax.tax_enable', true);
        self::getConfigManager()->set(
            'oro_tax.use_as_base_by_default',
            TaxationSettingsProvider::USE_AS_BASE_DESTINATION
        );
        self::getConfigManager()->set('oro_promotion.feature_enabled', true);
        self::getConfigManager()->flush();

        $this->clearCache();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->draftSessionOrmFilterManager->enable();

        self::getConfigManager()->set('oro_tax.tax_enable', $this->isTaxationEnabled);
        self::getConfigManager()->set('oro_tax.use_as_base_by_default', $this->taxationUseAsBaseOption);
        self::getConfigManager()->set('oro_promotion.feature_enabled', $this->isPromotionEnabled);
        self::getConfigManager()->flush();
    }

    private function clearCache(): void
    {
        self::getContainer()->get('oro_tax.taxation_provider.cache')->clear();
        $matchers = self::getContainer()->get('oro_tax.address_matcher_registry')->getMatchers();
        foreach ($matchers as $matcher) {
            if ($matcher instanceof ResetInterface) {
                $matcher->reset();
            }
        }
    }

    public function testReturns404ForNonExistentOrder(): void
    {
        $nonExistentOrderId = 0;
        $draftSessionUuid = UUIDGenerator::v4();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $nonExistentOrderId,
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'quantity' => 5,
                ],
            ]
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 404);
    }

    public function testReturns404WhenOrderDraftNotExists(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_2);
        $draftSessionUuid = UUIDGenerator::v4();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_2);

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'quantity' => 5,
                ],
            ]
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 404);
    }

    public function testReturns404ForNonExistentLineItem(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $nonExistentId = 0;
        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $nonExistentId,
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'quantity' => 5,
                ],
            ]
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 404);
    }

    public function testReturnsEmptyTaxesWhenTaxationDisabled(): void
    {
        self::getConfigManager()->set('oro_tax.tax_enable', false);
        self::getConfigManager()->flush();

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'quantity' => 10,
                ],
            ]
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemTaxesHtml', $data);
        self::assertEmpty($data['lineItemTaxesHtml'], 'Tax HTML should be empty when taxation is disabled');

        self::assertArrayHasKey('lineItemDiscountsHtml', $data);
        self::assertNotEmpty($data['lineItemDiscountsHtml']);
    }

    public function testCalculateTaxesActionReturnsTaxesWhenLineItemDraftNotExists(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'product' => $lineItem->getProduct()->getId(),
                    'productUnit' => $lineItem->getProductUnit()->getCode(),
                    'price' => [
                        'value' => '100',
                        'currency' => $lineItem->getPrice()->getCurrency()
                    ],
                    'priceType' => $lineItem->getPriceType(),
                    'quantity' => '3',
                ],
            ]
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemTaxesHtml', $data);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $expectedTaxesHtml = $twig->render('@OroOrder/Order/orderLineItemDraftTaxes.html.twig', [
            'lineItemTaxes' => [
                'unit' => [
                    'includingTax' => '120.8',
                    'excludingTax' => '100',
                    'taxAmount' => '20.8',
                    'adjustment' => '0',
                    'currency' => 'USD',
                ],
                'row' => [
                    'includingTax' => '362.4',
                    'excludingTax' => '300',
                    'taxAmount' => '62.4',
                    'adjustment' => '0',
                    'currency' => 'USD',
                ],
                'taxes' => [
                    [
                        'tax' => 'TAX1',
                        'rate' => '0.104',
                        'taxableAmount' => '600',
                        'taxAmount' => '62.4',
                        'currency' => 'USD',
                    ],
                ],
            ]
        ]);

        self::assertEquals($expectedTaxesHtml, $data['lineItemTaxesHtml']);

        self::assertArrayHasKey('lineItemDiscountsHtml', $data);
        self::assertNotEmpty($data['lineItemDiscountsHtml']);
    }

    public function testCalculateTaxesActionReturnsTaxesWhenLineItemDraftExists(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        /** @var OrderLineItem $lineItemDraft */
        $lineItemDraft = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftSessionUuid = $lineItemDraft->getDraftSessionUuid();

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'product' => $lineItemDraft->getProduct()->getId(),
                    'productUnit' => $lineItemDraft->getProductUnit()->getCode(),
                    'price' => [
                        'value' => '10',
                        'currency' => $lineItemDraft->getPrice()->getCurrency()
                    ],
                    'priceType' => $lineItemDraft->getPriceType(),
                    'quantity' => '2',
                ],
            ]
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemTaxesHtml', $data);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $expectedTaxesHtml = $twig->render('@OroOrder/Order/orderLineItemDraftTaxes.html.twig', [
            'lineItemTaxes' => [
                'unit' => [
                    'includingTax' => '12.08',
                    'excludingTax' => '10',
                    'taxAmount' => '2.08',
                    'adjustment' => '0',
                    'currency' => 'USD',
                ],
                'row' => [
                    'includingTax' => '24.16',
                    'excludingTax' => '20',
                    'taxAmount' => '4.16',
                    'adjustment' => '0',
                    'currency' => 'USD',
                ],
                'taxes' => [
                    [
                        'tax' => 'TAX1',
                        'rate' => '0.104',
                        'taxableAmount' => '40',
                        'taxAmount' => '4.16',
                        'currency' => 'USD',
                    ],
                ],
            ]
        ]);

        self::assertEquals($expectedTaxesHtml, $data['lineItemTaxesHtml']);

        self::assertArrayHasKey('lineItemDiscountsHtml', $data);
        self::assertNotEmpty($data['lineItemDiscountsHtml']);
    }

    public function testCalculateTaxesActionReturnsRecalculatedTaxesWhenOneOfLineItemsChanged(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var OrderLineItem $lineItem1 */
        $lineItem1 = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);

        // Create line item 2 in the order draft
        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($lineItem1->getProduct());
        $lineItem2->setProductSku($lineItem1->getProductSku());
        $lineItem2->setProductUnit($lineItem1->getProductUnit());
        $lineItem2->setQuantity(5);
        $lineItem2->setPrice(Price::create(100, 'USD'));
        $lineItem2->setPriceType($lineItem1->getPriceType());
        $lineItem2->setDraftSessionUuid($draftSessionUuid);
        $orderDraft->addLineItem($lineItem2);

        $entityManager->persist($orderDraft);
        $entityManager->flush();

        // First request: Calculate taxes for line item 1
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem1->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'product' => $lineItem1->getProduct()->getId(),
                    'productUnit' => $lineItem1->getProductUnit()->getCode(),
                    'price' => [
                        'value' => '100',
                        'currency' => 'USD'
                    ],
                    'priceType' => $lineItem1->getPriceType(),
                    'quantity' => '10',
                ],
            ]
        );

        $result = $this->client->getResponse();
        $firstData = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemTaxesHtml', $firstData);
        self::assertArrayHasKey('lineItemDiscountsHtml', $firstData);

        $firstTaxesHtml = $firstData['lineItemTaxesHtml'];
        $firstDiscountsHtml = $firstData['lineItemDiscountsHtml'];

        // Change tax rate
        $tax = $entityManager->getRepository(Tax::class)->findOneBy(['code' => 'TAX1']);
        $originalRate = $tax->getRate();
        $tax->setRate(0.2); // Change from 10.4% to 20%
        $entityManager->flush();

        // Update line item 2 quantity via doctrine
        $lineItem2->setQuantity(20);
        $entityManager->flush();

        // Reinitialize client to reset request state
        $this->initClient([], self::generateBasicAuthHeader());
        $this->clearCache();

        // Second request: Calculate taxes for line item 1 again after tax rate and line item 2's quantity changed
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem1->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'product' => $lineItem1->getProduct()->getId(),
                    'productUnit' => $lineItem1->getProductUnit()->getCode(),
                    'price' => [
                        'value' => '100',
                        'currency' => 'USD'
                    ],
                    'priceType' => $lineItem1->getPriceType(),
                    'quantity' => '10',
                ],
            ]
        );

        $result = $this->client->getResponse();
        $secondData = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemTaxesHtml', $secondData);
        self::assertArrayHasKey('lineItemDiscountsHtml', $secondData);

        $secondTaxesHtml = $secondData['lineItemTaxesHtml'];
        $secondDiscountsHtml = $secondData['lineItemDiscountsHtml'];

        // Restore original tax rate
        $tax->setRate($originalRate);
        $entityManager->flush();

        // Verify that taxes for line item 1 changed due to tax rate change
        self::assertNotEquals(
            $firstTaxesHtml,
            $secondTaxesHtml,
            'Taxes HTML for line item 1 should change when tax rate changes'
        );

        // Verify that discounts for line item 1 remain consistent
        // (line item discounts are calculated per line item based on its own values, not affected by tax rate)
        self::assertEquals(
            $firstDiscountsHtml,
            $secondDiscountsHtml,
            'Discounts HTML for line item 1 should remain consistent when only tax rate changes'
        );
    }

    public function testReturnsNoDiscountsWhenPromotionsDisabled(): void
    {
        self::getConfigManager()->set('oro_promotion.feature_enabled', false);
        self::getConfigManager()->flush();

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'product' => $lineItem->getProduct()->getId(),
                    'productUnit' => $lineItem->getProductUnit()->getCode(),
                    'price' => [
                        'value' => '100',
                        'currency' => $lineItem->getPrice()->getCurrency()
                    ],
                    'priceType' => $lineItem->getPriceType(),
                    'quantity' => '10',
                ],
            ]
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemDiscountsHtml', $data);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $expectedDiscountsHtml = $twig->render('@OroOrder/Order/orderLineItemDraftDiscounts.html.twig', [
            'lineItemDiscounts' => [
                'appliedDiscountsAmount' => '0',
                'rowTotalAfterDiscountExcludingTax' => '1000',
                'rowTotalAfterDiscountIncludingTax' => '1000',
                'currency' => 'USD',
            ]
        ]);

        self::assertEquals($expectedDiscountsHtml, $data['lineItemDiscountsHtml']);

        self::assertArrayHasKey('lineItemTaxesHtml', $data);
        self::assertNotEmpty($data['lineItemTaxesHtml']);
    }

    public function testReturnsDiscountsWhenLineItemDraftNotExists(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'product' => $lineItem->getProduct()->getId(),
                    'productUnit' => $lineItem->getProductUnit()->getCode(),
                    'price' => [
                        'value' => '100',
                        'currency' => $lineItem->getPrice()->getCurrency()
                    ],
                    'priceType' => $lineItem->getPriceType(),
                    'quantity' => '3',
                ],
            ]
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemDiscountsHtml', $data);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $expectedDiscountsHtml = $twig->render('@OroOrder/Order/orderLineItemDraftDiscounts.html.twig', [
            'lineItemDiscounts' => [
                'appliedDiscountsAmount' => '30',
                'rowTotalAfterDiscountExcludingTax' => '270',
                'rowTotalAfterDiscountIncludingTax' => '332.4',
                'currency' => 'USD',
            ]
        ]);

        self::assertEquals($expectedDiscountsHtml, $data['lineItemDiscountsHtml']);

        self::assertArrayHasKey('lineItemTaxesHtml', $data);
        self::assertNotEmpty($data['lineItemTaxesHtml']);
    }

    public function testReturnsDiscountsWhenLineItemDraftExists(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);
        /** @var OrderLineItem $lineItemDraft */
        $lineItemDraft = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_DRAFT_1);
        $draftSessionUuid = $lineItemDraft->getDraftSessionUuid();

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'product' => $lineItemDraft->getProduct()->getId(),
                    'productUnit' => $lineItemDraft->getProductUnit()->getCode(),
                    'price' => [
                        'value' => '10',
                        'currency' => $lineItemDraft->getPrice()->getCurrency()
                    ],
                    'priceType' => $lineItemDraft->getPriceType(),
                    'quantity' => '2',
                ],
            ]
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemDiscountsHtml', $data);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $expectedDiscountsHtml = $twig->render('@OroOrder/Order/orderLineItemDraftDiscounts.html.twig', [
            'lineItemDiscounts' => [
                'appliedDiscountsAmount' => '2',
                'rowTotalAfterDiscountExcludingTax' => '18',
                'rowTotalAfterDiscountIncludingTax' => '22.16',
                'currency' => 'USD',
            ]
        ]);

        self::assertEquals($expectedDiscountsHtml, $data['lineItemDiscountsHtml']);

        self::assertArrayHasKey('lineItemTaxesHtml', $data);
        self::assertNotEmpty($data['lineItemTaxesHtml']);
    }

    public function testReturnsDiscountsWhenTaxationDisabled(): void
    {
        self::getConfigManager()->set('oro_tax.tax_enable', false);
        self::getConfigManager()->flush();

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemDraftData::ORDER_LINE_ITEM_1);

        $draftSessionUuid = UUIDGenerator::v4();
        $orderDraft = self::getContainer()->get('oro_order.draft_session.factory.order')
            ->createDraft($order, $draftSessionUuid);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $entityManager->persist($orderDraft);
        $entityManager->flush();

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_order_line_item_draft_calculate_taxes_and_discounts',
                [
                    'orderId' => $order->getId(),
                    'orderLineItemId' => $lineItem->getId(),
                    'orderDraftSessionUuid' => $draftSessionUuid,
                ]
            ),
            [
                'oro_order_line_item_draft' => [
                    'product' => $lineItem->getProduct()->getId(),
                    'productUnit' => $lineItem->getProductUnit()->getCode(),
                    'price' => [
                        'value' => '100',
                        'currency' => $lineItem->getPrice()->getCurrency()
                    ],
                    'priceType' => $lineItem->getPriceType(),
                    'quantity' => '3',
                ],
            ]
        );

        $result = $this->client->getResponse();
        $data = self::getJsonResponseContent($result, 200);

        self::assertArrayHasKey('lineItemDiscountsHtml', $data);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        // When taxation is disabled, the discount response should contain rowTotalAfterDiscount
        // instead of the tax-specific fields (rowTotalAfterDiscountExcludingTax, rowTotalAfterDiscountIncludingTax)
        $expectedDiscountsHtml = $twig->render('@OroOrder/Order/orderLineItemDraftDiscounts.html.twig', [
            'lineItemDiscounts' => [
                'appliedDiscountsAmount' => '30',
                'rowTotalAfterDiscount' => '270',
                'currency' => 'USD',
            ]
        ]);

        self::assertEquals($expectedDiscountsHtml, $data['lineItemDiscountsHtml']);

        self::assertArrayHasKey('lineItemTaxesHtml', $data);
        self::assertEmpty($data['lineItemTaxesHtml'], 'Tax HTML should be empty when taxation is disabled');
    }
}
