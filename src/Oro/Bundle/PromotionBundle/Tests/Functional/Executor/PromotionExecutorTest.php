<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Executor;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PromotionBundle\DependencyInjection\Configuration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCheckoutData;
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
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $this->client->useHashNavigation(true);

        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->loadFixtures([
            LoadCheckoutData::class,
            LoadCombinedProductPrices::class,
            LoadPromotionDiscountData::class
        ]);

        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::EMAIL);
        // Request needed for emulation front store request
        $this->getContainer()->get('request_stack')->push(new Request());
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

        $this->getContainer()->get('doctrine')->getManagerForClass(Promotion::class)->flush();

        // Change calculation strategy
        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->configManager->set('oro_promotion.' . Configuration::DISCOUNT_STRATEGY, $strategy);
        $this->configManager->flush();

        // Execute calculation
        $executor = $this->getContainer()->get('oro_promotion.promotion_executor');
        $checkout = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);

        $actualDiscountContext = $executor->execute($checkout);

        // Check totals
        $this->assertSame($expected['totalLineItemsDiscount'], $actualDiscountContext->getTotalLineItemsDiscount());
        $this->assertSame($expected['subtotalDiscountTotal'], $actualDiscountContext->getSubtotalDiscountTotal());
        $this->assertSame($expected['discountAmount'], $actualDiscountContext->getTotalDiscountAmount());
        $this->assertSame($expected['shippingDiscountTotal'], $actualDiscountContext->getShippingDiscountTotal());
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
                    'promo_order_12.777_USD',
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 22.777,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 22.777,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.777_USD',
                    'promo_buy_x_get_y_2_USD_apply_to_each_y',
                    'promo_buy_x_get_y_2_USD_apply_to_xy_total_with_limit1',
                    'promo_buy_x_get_y_10%_apply_to_xy_total_with_limit1'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 7.76,
                    'subtotalDiscountTotal' => 22.777,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 30.537,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.777_USD',
                    'promo_buy_x_get_y_2_USD_apply_to_each_y',
                    'promo_buy_x_get_y_2_USD_apply_to_xy_total_with_limit1',
                    'promo_buy_x_get_y_10%_apply_to_xy_total_with_limit1'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 12.777,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 12.777,

                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.777_USD',
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 12.777,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 12.777,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.777_USD',
                    'promo_line_item_each_1_USD',
                    'promo_line_item_each_20%'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 11.6,
                    'subtotalDiscountTotal' => 22.777,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 34.377,
                ]
            ],
            [
                'enabledPromotions' => [
                    'promo_order_0_USD',
                    'promo_order_10_USD',
                    'promo_order_10_EUR',
                    'promo_order_12.777_USD',
                    'promo_line_item_each_1_USD',
                    'promo_line_item_each_20%'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 12.777,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 12.777,
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
                    'promo_order_12.777_USD',
                    'promo_line_item_each_1_USD',
                    'promo_line_item_each_20%'
                ],
                'strategy' => 'apply_all',
                'expected' => [
                    'totalLineItemsDiscount' => 17.808,
                    'subtotalDiscountTotal' => 22.777,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 40.585,
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
                    'promo_order_50%_without_scope',
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
                    'promo_order_50%_without_scope',
                    'promo_order_10_USD_stop_processing',
                    'promo_order_15%'
                ],
                'strategy' => 'profitable',
                'expected' => [
                    'totalLineItemsDiscount' => 0.0,
                    'subtotalDiscountTotal' => 10.0,
                    'shippingDiscountTotal' => 0.0,
                    'discountAmount' => 10.0,
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
// TODO uncomment after merge of BB-10808
//            'case for fixed amount shipping discount, with profitable strategy, also covers shipping filtering' => [
//                'enabledPromotions' => [
//                    'promo_order_10_USD',
//                    'promo_shipping_10_USD_unsupported_method',
//                    'promo_shipping_10_USD_flat_rate_method',
//                ],
//                'strategy' => 'profitable',
//                'expected' => [
//                    'totalLineItemsDiscount' => 0.0,
//                    'subtotalDiscountTotal' => 0.0,
//                    'shippingDiscountTotal' => 20.0,
//                    'discountAmount' => 20.0
//                ]
//            ],
//            'case for percent shipping discount, with apply all strategy' => [
//                'enabledPromotions' => [
//                    'promo_order_10_USD',
//                    'promo_shipping_10_USD_flat_rate_method',
//                ],
//                'strategy' => 'apply_all',
//                'expected' => [
//                    'totalLineItemsDiscount' => 0.0,
//                    'subtotalDiscountTotal' => 10.0,
//                    'shippingDiscountTotal' => 20.0,
//                    'discountAmount' => 30.0
//                ]
//            ],
        ];
    }
}
