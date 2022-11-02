<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class CustomerUserAddressForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
{
    /** @var bool */
    private $originalGuestCheckoutOptionValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            LoadCustomerUserData::class,
            '@OroCustomerBundle/Tests/Functional/Api/Frontend/DataFixtures/customer_user_address.yml'
        ]);
        $this->originalGuestCheckoutOptionValue = $this->getGuestCheckoutOptionValue();
        if (!$this->originalGuestCheckoutOptionValue) {
            $this->setGuestCheckoutOptionValue(true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->getGuestCheckoutOptionValue() !== $this->originalGuestCheckoutOptionValue) {
            $this->setGuestCheckoutOptionValue($this->originalGuestCheckoutOptionValue);
        }
        $this->originalGuestCheckoutOptionValue = null;
    }

    private function getGuestCheckoutOptionValue(): bool
    {
        return $this->getConfigManager()->get('oro_checkout.guest_checkout');
    }

    private function setGuestCheckoutOptionValue(bool $value): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', $value);
        $configManager->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'customeruseraddresses']);
        self::assertResponseCount(0, $response);
    }

    public function testGetListForExistingGuestCustomerUser(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $responseContent = self::jsonToArray($response->getContent());
        $guestCustomerUserAddressId = (int)$responseContent['data']['relationships']['addresses']['data'][0]['id'];

        $response = $this->cget(['entity' => 'customeruseraddresses']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customeruseraddresses',
                        'id'   => (string)$guestCustomerUserAddressId
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForGuestCustomerUser(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $responseContent = self::jsonToArray($response->getContent());
        $guestCustomerUserAddressId = (int)$responseContent['data']['relationships']['addresses']['data'][0]['id'];

        $response = $this->get(['entity' => 'customeruseraddresses', 'id' => (string)$guestCustomerUserAddressId]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customeruseraddresses',
                    'id'   => (string)$guestCustomerUserAddressId
                ]
            ],
            $response
        );
    }

    public function testTryToGetForNotGuestCustomerUser(): void
    {
        $response = $this->get(
            ['entity' => 'customeruseraddresses', 'id' => '<toString(@customer_user_address1->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testCreateForGuestCustomerUser()
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $guestCustomerUserId = (int)$this->getResourceId($response);

        $data = $this->getRequestData('create_customer_user_address_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$guestCustomerUserId;
        $response = $this->post(['entity' => 'customeruseraddresses'], $data);
        $this->assertResponseContains($data, $response);
    }

    public function testCreateForNotGuestCustomerUser(): void
    {
        $data = $this->getRequestData('create_customer_user_address_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = '<toString(@customer_user1->id)>';
        $response = $this->post(['entity' => 'customeruseraddresses'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'access granted constraint',
                'detail' => 'No access to the entity.',
                'source' => ['pointer' => '/data/relationships/customerUser/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutCustomerUser()
    {
        $data = $this->getRequestData('create_customer_user_address_guest_checkout.yml');
        unset($data['data']['relationships']['customerUser']);
        $response = $this->post(['entity' => 'customeruseraddresses'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/customerUser/data']
            ],
            $response
        );
    }

    public function testTryToUpdate()
    {
        $addressId = $this->getReference('another_customer_user_address1')->getId();

        $response = $this->patch(
            ['entity' => 'customeruseraddresses', 'id' => (string)$addressId],
            [
                'data' => [
                    'type'       => 'customeruseraddresses',
                    'id'         => (string)$addressId,
                    'attributes' => [
                        'firstName' => 'Updated First Name'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToDelete()
    {
        $addressId = $this->getReference('another_customer_user_address1')->getId();

        $response = $this->delete(
            ['entity' => 'customeruseraddresses', 'id' => (string)$addressId],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToDeleteList()
    {
        $addressId = $this->getReference('another_customer_user_address1')->getId();

        $response = $this->cdelete(
            ['entity' => 'customeruseraddresses'],
            ['filter' => ['id' => (string)$addressId]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }
}
