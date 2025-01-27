<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class StartCheckoutForVisitorTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            LoadCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    public function testTryToStartCheckout(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The guest checkout is disabled.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToStartCheckoutForVisitorWithGuestCheckout(): void
    {
        $configManager = self::getConfigManager();
        $originalGuestCheckoutOptionValue = $configManager->get('oro_checkout.guest_checkout');
        self::assertFalse($originalGuestCheckoutOptionValue);
        $configManager->set('oro_checkout.guest_checkout', true);
        $configManager->flush();

        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order4->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to this type of parent entities.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
