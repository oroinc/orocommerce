<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadGuestCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class CheckoutAddressForAnonymousVisitorTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableAnonymousVisitor();
        $this->setAnonymousVisitorCookie();
        $this->loadFixtures([
            LoadCustomerData::class,
            LoadCustomerUserData::class,
            LoadGuestCheckoutData::class
        ]);

        $this->setGuestCheckoutOptionValue(true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->setGuestCheckoutOptionValue(false);
        parent::tearDown();
    }

    private function setGuestCheckoutOptionValue(bool $value): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', $value);
        $configManager->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutaddresses'], [], [], false);
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST');
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '<toString(@checkout.in_progress.billing_address->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
