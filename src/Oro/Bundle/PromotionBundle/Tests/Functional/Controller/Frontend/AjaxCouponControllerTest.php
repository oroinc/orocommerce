<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCheckoutData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxCouponControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadCouponData::class,
            LoadCheckoutData::class,
        ]);
    }

    public function testValidateCouponApplicabilityAction()
    {
        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_promotion_frontend_add_coupon'),
            [
                'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)
                    ->getCode(),
                'entityClass' => Checkout::class,
                'entityId' => $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1)->getId(),
            ]
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);
        self::assertFalse($jsonContent['success']);
    }
}
