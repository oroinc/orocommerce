<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountriesAndRegions;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CustomerForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
{
    private const string ENABLE_GUEST_CHECKOUT = 'oro_checkout.guest_checkout';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            LoadCountriesAndRegions::class,
            '@OroCustomerBundle/Tests/Functional/ApiFrontend/DataFixtures/customer_user.yml'
        ]);
        // guard
        self::assertFalse(self::getConfigManager()->get(self::ENABLE_GUEST_CHECKOUT));
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
        $configManager->set(self::ENABLE_GUEST_CHECKOUT, $value);
        $configManager->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'customers']);
        self::assertResponseCount(0, $response);
    }

    public function testGetListForExistingGuestCustomerUser(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout_min.yml');
        $guestCustomerUserId = (int)$this->getResourceId($response);
        $guestCustomerId = $this->getEntityManager()->find(CustomerUser::class, $guestCustomerUserId)
            ->getCustomer()
            ->getId();

        $response = $this->cget(['entity' => 'customers']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customers',
                        'id' => (string)$guestCustomerId
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForGuestCustomer(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout_min.yml');
        $guestCustomerUserId = (int)$this->getResourceId($response);
        $guestCustomerId = $this->getEntityManager()->find(CustomerUser::class, $guestCustomerUserId)
            ->getCustomer()
            ->getId();

        $response = $this->get(['entity' => 'customers', 'id' => (string)$guestCustomerId]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customers',
                    'id' => (string)$guestCustomerId
                ]
            ],
            $response
        );
    }

    public function testTryToGetForNotGuestCustomer(): void
    {
        $response = $this->get(
            ['entity' => 'customers', 'id' => '<toString(@customer1->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'customers'],
            [
                'data' => [
                    'type' => 'customers',
                    'attributes' => [
                        'name' => 'New Customer'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $customerId = $this->getReference('customer1')->getId();

        $response = $this->patch(
            ['entity' => 'customers', 'id' => (string)$customerId],
            [
                'data' => [
                    'type' => 'customers',
                    'id' => (string)$customerId,
                    'attributes' => [
                        'name' => 'Updated Name'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $customerId = $this->getReference('customer1')->getId();

        $response = $this->delete(
            ['entity' => 'customers', 'id' => (string)$customerId],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'customers'],
            ['filter[email]' => 'user1@example.com'],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
