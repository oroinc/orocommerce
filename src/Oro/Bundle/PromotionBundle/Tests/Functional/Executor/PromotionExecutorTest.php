<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Executor;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PromotionBundle\DependencyInjection\Configuration;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCheckoutData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadOrderData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionDiscountData;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class PromotionExecutorTest extends FrontendWebTestCase
{
    /**
     * @var ConfigManager $configManager
     */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $this->client->useHashNavigation(true);

        $this->configManager = static::getContainer()->get('oro_config.manager');
        $this->loadFixtures([
            LoadOrderData::class,
            LoadCheckoutData::class,
            LoadCombinedProductPrices::class,
            LoadPromotionDiscountData::class
        ]);

        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::EMAIL);
        // Request needed for emulation front store request
        static::getContainer()->get('request_stack')->push(new Request());
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $enabledPromotions
     * @param string $strategy
     * @param array $expected
     */
    public function testExecute(array $enabledPromotions, $strategy, array $expected)
    {
        // Enable only necessary promotions
        foreach ($enabledPromotions as $promotion) {
            /** @var Promotion $promotion */
            $promotion = $this->getReference($promotion);

            $promotion->getRule()->setEnabled(true);
        }

        static::getContainer()->get('doctrine')->getManagerForClass(Promotion::class)->flush();

        // Change calculation strategy
        $this->configManager = static::getContainer()->get('oro_config.manager');
        $this->configManager->set('oro_promotion.' . Configuration::DISCOUNT_STRATEGY, $strategy);
        $this->configManager->flush();

        // Execute calculation
        $executor = static::getContainer()->get('oro_promotion.promotion_executor');
        $checkout = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);

        $actualDiscountContext = $executor->execute($checkout);

        // Check totals
        static::assertSame($expected['totalLineItemsDiscount'], $actualDiscountContext->getTotalLineItemsDiscount());
        static::assertSame($expected['subtotalDiscountTotal'], $actualDiscountContext->getSubtotalDiscountTotal());
        static::assertSame($expected['discountAmount'], $actualDiscountContext->getTotalDiscountAmount());
        static::assertSame($expected['shippingDiscountTotal'], $actualDiscountContext->getShippingDiscountTotal());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.77_USD',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 22.77,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 22.77,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.77_USD',
                    'promo_buy_x_get_y_2_USD_apply_to_each_y',
                    'promo_buy_x_get_y_2_USD_apply_to_xy_total_with_limit1',
                    'promo_buy_x_get_y_10%_apply_to_xy_total_with_limit1'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 7.76,
                    'subtotalDiscountTotal' => 22.77,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 30.53,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.77_USD',
                    'promo_buy_x_get_y_2_USD_apply_to_each_y',
                    'promo_buy_x_get_y_2_USD_apply_to_xy_total_with_limit1',
                    'promo_buy_x_get_y_10%_apply_to_xy_total_with_limit1'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 12.77,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 12.77,

                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.77_USD',
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 12.77,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 12.77,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.77_USD',
                    'promo_line_item_each_1_USD',
                    'promo_line_item_each_20%'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 11.6,
                    'subtotalDiscountTotal' => 22.77,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 34.37,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.77_USD',
                    'promo_line_item_each_1_USD',
                    'promo_line_item_each_20%'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 12.77,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 12.77,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_buy_x_get_y_2_USD_apply_to_each_y',
                    'promo_buy_x_get_y_2_USD_apply_to_xy_total_with_limit1',
                    'promo_buy_x_get_y_10%_apply_to_xy_total_with_limit1',
                    'promo_order_12.77_USD',
                    'promo_line_item_each_1_USD',
                    'promo_line_item_each_20%'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 17.808,
                    'subtotalDiscountTotal' => 22.77,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 40.578,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD_stop_processing',
                    'promo_order_15.89%',
                    'promo_order_12.78%'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 0.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD_stop_processing',
                    'promo_order_15.89%',
                    'promo_order_12.78%'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 0.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0%',
                    'promo_order_10_USD_stop_processing',
                    'promo_order_15%'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 10.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 10.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0%',
                    'promo_order_50%_without_scope', // if promotion has no scope it's applied
                    'promo_order_10_USD_stop_processing',
                    'promo_order_15%'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 35.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 35.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0%',
                    'promo_order_50%_without_scope', // if promotion has no scope it's applied
                    'promo_order_10_USD_stop_processing',
                    'promo_order_15%'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 25.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 25.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_20%',
                    'promo_order_33_USD_unsuitable_expression',
                    'promo_order_20_USD_with_expression',
                    'promo_order_40%'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 30.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 30.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_20%',
                    'promo_order_33_USD_unsuitable_expression',
                    'promo_order_20_USD_with_expression',
                    'promo_order_40%'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 20.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 20.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_25%',
                    'promo_order_25_USD',
                    'promo_order_40%_stop_processing'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 42.5,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 42.5,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_25%',
                    'promo_order_25_USD',
                    'promo_order_40%_stop_processing'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 25.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 25.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_1_USD',
                    'promo_order_2_USD',
                    'promo_order_3_USD'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 4.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 4.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_1_USD',
                    'promo_order_2_USD',
                    'promo_order_3_USD'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 3.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 3.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_15_USD',
                    'promo_order_45_USD',
                    'promo_order_30_USD'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 45.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 45.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_15_USD',
                    'promo_order_45_USD',
                    'promo_order_30_USD'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 30.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 30.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_5_USD',
                    'promo_order_100%_expired_schedule',
                    'promo_order_5_USD_without_matching_products',
                    'promo_order_42_USD'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 47.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 47.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_5_USD',
                    'promo_order_100%_expired_schedule',
                    'promo_order_5_USD_without_matching_products',
                    'promo_order_42_USD'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 42.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 42.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_line_item_each_1_USD_stop_processing',
                    'promo_line_item_each_50%',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 2.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 2.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_line_item_each_1_USD_stop_processing',
                    'promo_line_item_each_50%',
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 2.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 2.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_line_item_total_1_USD',
                    'promo_line_item_total_40%',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 21.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 21.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_line_item_total_1_USD',
                    'promo_line_item_total_40%',
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 20.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 20.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_buy_x_get_y_2_USD_apply_to_each_y',
                    'promo_buy_x_get_y_2_USD_apply_to_xy_total_with_limit1',
                    'promo_buy_x_get_y_10%_apply_to_xy_total_with_limit1',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 7.76,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 7.76,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_buy_x_get_y_2_USD_apply_to_each_y',
                    'promo_buy_x_get_y_2_USD_apply_to_each_y_with_limit1',
                    'promo_buy_x_get_y_10%_apply_to_each_y_with_limit1',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 6.88,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 6.88,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_buy_x_get_y_10%_apply_to_each_y_with_limit1',
                    'promo_buy_x_get_y_2_USD_apply_to_xy_total_with_limit1',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 3.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 3.0,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_buy_x_get_y_10%_apply_to_each_y',
                    'promo_buy_x_get_y_2_USD_apply_to_xy_total',
                    'promo_buy_x_get_y_10%_apply_to_xy_total',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 9.52,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 9.52,
                ]
            ],
            'case for fixed amount shipping discount, with profitable strategy, also covers shipping filtering' => [
                'enabledPromotions' => [
                    'promo_order_10_USD',
                    'promo_shipping_10_USD_unsupported_method',
                    'promo_shipping_20_USD_flat_rate_method',
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 10.0,
                    'shippingDiscountTotal' => 20.0,
                    'discountAmount' => 30.0
                ]
            ],
            'case for fixed amount and percent shipping discount, with profitable strategy' => [
                'enabledPromotions' => [
                    'promo_shipping_20_USD_flat_rate_method',
                    'promo_shipping_20%_flat_rate_method',
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 20.0,
                    'discountAmount' => 20.0
                ]
            ],
            'case for percent shipping discount, with apply all strategy' => [
                'enabledPromotions' => [
                    'promo_order_10_USD',
                    'promo_shipping_20_USD_flat_rate_method',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 10.0,
                    'shippingDiscountTotal' => 20.0,
                    'discountAmount' => 30.0
                ]
            ],
        ];
    }

    /**
     * @dataProvider executeAppliedPromotionsDataProvider
     * @param array $enabledPromotions
     * @param array $appliedPromotions
     * @param string $strategy
     * @param array $expected
     */
    public function testExecuteWithAppliedPromotions(
        array $enabledPromotions,
        array $appliedPromotions,
        $strategy,
        array $expected
    ) {
        // Enable only necessary promotions
        foreach ($enabledPromotions as $promotion) {
            /** @var Promotion $promotion */
            $promotion = $this->getReference($promotion);

            $promotion->getRule()->setEnabled(true);
        }

        /** @var Order $order */
        $order = $this->getReference(LoadOrderData::PROMOTION_ORDER_1);

        /** @var AppliedPromotionMapper $appliedPromotionMapper */
        $appliedPromotionMapper = static::getContainer()->get('oro_promotion.mapper.applied_promotion');
        $entityManager = static::getContainer()->get('doctrine')->getManagerForClass(Promotion::class);

        // Create applied discounts data for order based on applied promotions
        foreach ($appliedPromotions as $appliedPromotionData) {
            /** @var Promotion $basePromotion */
            $basePromotion = $this->getReference($appliedPromotionData['basePromotion']);

            $appliedPromotion = new AppliedPromotion();
            $appliedPromotionMapper->mapPromotionDataToAppliedPromotion($appliedPromotion, $basePromotion, $order);

            $configOptions = array_merge(
                $basePromotion->getDiscountConfiguration()->getOptions(),
                $appliedPromotionData['configOptions']
            );
            $appliedPromotion->setConfigOptions($configOptions);

            $appliedPromotion->setActive($appliedPromotionData['enabled']);

            $appliedDiscount = new AppliedDiscount();
            $appliedDiscount
                ->setAppliedPromotion($appliedPromotion)
                ->setCurrency('USD')
                ->setAmount(1234); //Some irrelevant data

            $appliedPromotion->addAppliedDiscount($appliedDiscount);

            $entityManager->persist($appliedPromotion);
            $entityManager->persist($appliedDiscount);
        }

        $entityManager->persist($order);

        $entityManager->flush();

        // Change calculation strategy
        $this->configManager = static::getContainer()->get('oro_config.manager');
        $this->configManager->set('oro_promotion.' . Configuration::DISCOUNT_STRATEGY, $strategy);
        $this->configManager->flush();

        // Execute calculation
        $executor = static::getContainer()->get('oro_promotion.promotion_executor');

        $actualDiscountContext = $executor->execute($order);

        // Check totals
        static::assertSame($expected['totalLineItemsDiscount'], $actualDiscountContext->getTotalLineItemsDiscount());
        static::assertSame($expected['subtotalDiscountTotal'], $actualDiscountContext->getSubtotalDiscountTotal());
        static::assertSame($expected['discountAmount'], $actualDiscountContext->getTotalDiscountAmount());
        static::assertSame($expected['shippingDiscountTotal'], $actualDiscountContext->getShippingDiscountTotal());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function executeAppliedPromotionsDataProvider()
    {
        return [
            'test that applied order discount with previous configuration is used' => [
                'enabledPromotions' => [
                    'promo_order_12.77_USD',
                ],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_order_12.77_USD',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 10.55
                        ],
                        'enabled' => true
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 10.55,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 10.55,
                ]
            ],
            'test that applied shipping discount with previous configuration is used' => [
                'enabledPromotions' => [
                    'promo_shipping_20_USD_flat_rate_method',
                ],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_shipping_20_USD_flat_rate_method',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 10.00
                        ],
                        'enabled' => true
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 10.0,
                    'discountAmount' => 10.0
                ]
            ],
            'test that applied line item discount with previous configuration is used' => [
                'enabledPromotions' => [
                    'promo_line_item_each_1_USD',
                ],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_line_item_each_1_USD',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 3.00
                        ],
                        'enabled' => true
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 6.0, // 3 * 2 (max quantity to which promo applies)
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 6.0
                ]
            ],
            'test disabled applied order discount' => [
                'enabledPromotions' => [],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_order_12.77_USD',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 10.55
                        ],
                        'enabled' => false
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 0.0
                ]
            ],
            'test disabled applied shipping discount' => [
                'enabledPromotions' => [],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_shipping_20_USD_flat_rate_method',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 10.00
                        ],
                        'enabled' => false
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 0.0
                ]
            ],
            'test disabled applied line item discount' => [
                'enabledPromotions' => [],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_line_item_each_1_USD',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 3.00
                        ],
                        'enabled' => false
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 0.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 0.0
                ]
            ],
            'test that duplicated applied order discounts do not affect discount amount' => [
                'enabledPromotions' => [],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_order_12.77_USD',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 10.55
                        ],
                        'enabled' => true
                    ],
                    [
                        'basePromotion' => 'promo_order_12.77_USD',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 10.55
                        ],
                        'enabled' => true
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 10.55,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 10.55
                ]
            ],
            //TODO: Test recalculation of applied discount when order changed?
            //TODO: Test that promotion data is used for conditions (e.g. expression or rule?)
            'test that disabled order applied discount gives zero discount amount' => [
                'enabledPromotions' => [],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_order_12.77_USD',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 10.55
                        ],
                        'enabled' => false
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 0.00,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 0.00,
                ]
            ],
            'test that disabled order discount gives zero discount amount' => [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.77_USD',
                ],
                'appliedPromotions' => [
                    [
                        'basePromotion' => 'promo_order_12.77_USD',
                        'isPromotionDelete' => false,
                        'configOptions' => [
                            'discount_value' => 10.55
                        ],
                        'enabled' => false
                    ]
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 10.00,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 10.00,
                ]
            ]
        ];
    }
}
