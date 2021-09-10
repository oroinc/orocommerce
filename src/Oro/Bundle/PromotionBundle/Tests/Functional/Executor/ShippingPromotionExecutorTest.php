<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Executor;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCheckoutData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadOrderData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionDiscountData;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class ShippingPromotionExecutorTest extends FrontendWebTestCase
{
    /**
     * @var PromotionExecutor
     */
    private $executor;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadOrderData::class,
            LoadCheckoutData::class,
            LoadCombinedProductPrices::class,
            LoadPromotionDiscountData::class
        ]);

        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::EMAIL);
        $container = $this->getContainer();
        // Request needed for emulation front store request
        $container->get('request_stack')->push(new Request());

        $this->executor = $container->get('oro_promotion.shipping_promotion_executor');
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $enabledPromotions, array $expected): void
    {
        $this->enablePromotions($enabledPromotions);

        $checkout = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);

        $actualDiscountContext = $this->executor->execute($checkout);

        $this->assertDiscountContextTotals($actualDiscountContext, $expected);
    }

    public function executeDataProvider(): array
    {
        return [
            'fixed amount' => [
                'enabledPromotions' => [
                    'promo_shipping_10_USD_unsupported_method',
                    'promo_shipping_20_USD_flat_rate_method',
                ],
                'expected' => [
                    'shippingDiscountTotal' => 20.0,
                ]
            ],
            'percent shipping discount' => [
                'enabledPromotions' => [
                    'promo_shipping_10_USD_unsupported_method',
                    'promo_shipping_20%_flat_rate_method',
                ],
                'expected' => [
                    'shippingDiscountTotal' => 4.0,
                ]
            ],
        ];
    }

    private function assertDiscountContextTotals(DiscountContextInterface $discountContext, array $expected): void
    {
        // Check totals
        $this->assertSame($expected['shippingDiscountTotal'], $discountContext->getShippingDiscountTotal());
    }

    private function enablePromotions(array $enabledPromotions): void
    {
        // Enable only necessary promotions
        foreach ($enabledPromotions as $promotion) {
            /** @var Promotion $promotion */
            $promotion = $this->getReference($promotion);

            $promotion->getRule()->setEnabled(true);
        }

        $this->getContainer()->get('doctrine')->getManagerForClass(Promotion::class)->flush();
    }
}
