<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxCouponControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadCouponData::class,
            LoadShoppingLists::class,
        ]);
    }

    public function testValidateCouponApplicabilityAction()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_promotion_frontend_add_coupon'),
            [
                'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getCode(),
                'entityClass' => ShoppingList::class,
                'entityId' => $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)->getId(),
            ]
        );
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $jsonContent = json_decode($result->getContent(), true);
        self::assertFalse($jsonContent['success']);
    }
}
