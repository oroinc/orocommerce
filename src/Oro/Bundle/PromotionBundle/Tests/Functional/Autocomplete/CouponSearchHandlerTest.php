<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\PromotionBundle\Autocomplete\CouponSearchHandler;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponSearchHandlerTest extends WebTestCase
{
    /**
     * @var CouponSearchHandler
     */
    private $searchHandler;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([LoadCouponData::class]);
        $this->searchHandler = self::getContainer()->get('oro_promotion.autocomplete.coupon_search_handler');
    }

    public function testSearch()
    {
        $result = $this->searchHandler->search('some_', 1, 10, false);
        $this->assertSearchResult($result, []);

        $result = $this->searchHandler->search('coupon_', 1, 10, false);
        $this->assertSearchResult(
            $result,
            [
                LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
                LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL,
                LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL
            ]
        );

        $result = $this->searchHandler->search('coupon_with_promo_and_valid', 1, 10, false);
        $this->assertSearchResult(
            $result,
            [
                LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
            ]
        );

        $result = $this->searchHandler->search('COUPON_', 1, 10, false);
        $this->assertSearchResult(
            $result,
            [
                LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
                LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL,
                LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL
            ]
        );

        // Try to search by promotion name
        $result = $this->searchHandler->search('Order PERCENT promotion name', 1, 10, false);
        $this->assertSearchResult(
            $result,
            [
                LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
                LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL
            ]
        );
    }

    /**
     * @param Coupon[] $result
     * @param array $expectedCodes
     */
    private function assertSearchResult(array $result, array $expectedCodes)
    {
        $searchItems = $result['results'];
        $resultCodes = [];
        array_map(function (array $searchResult) use (&$resultCodes) {
            $resultCodes[] = $searchResult['code'];
        }, $searchItems);

        sort($expectedCodes);
        sort($resultCodes);
        $this->assertEquals($expectedCodes, $resultCodes);
    }
}
