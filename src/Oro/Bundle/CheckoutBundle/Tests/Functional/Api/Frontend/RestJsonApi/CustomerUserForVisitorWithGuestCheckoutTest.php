<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountriesAndRegions;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerUserForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
{
    /** @var bool */
    private $originalGuestCheckoutOptionValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            LoadCountriesAndRegions::class,
            '@OroCustomerBundle/Tests/Functional/Api/Frontend/DataFixtures/customer_user.yml'
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

    private function getCurrentVisitor(): CustomerVisitor
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = self::getContainer()->get('security.token_storage');
        /** @var AnonymousCustomerUserToken $token */
        $token = $tokenStorage->getToken();
        self::assertInstanceOf(AnonymousCustomerUserToken::class, $token);

        return $token->getVisitor();
    }

    private function getCurrentWebsite(): Website
    {
        /** @var WebsiteManager $websiteManager */
        $websiteManager = self::getContainer()->get('oro_website.manager');

        return $websiteManager->getCurrentWebsite();
    }

    private function getDefaultUser(): ?User
    {
        /** @var DefaultUserProvider $defaultUserProvider */
        $defaultUserProvider = self::getContainer()->get('oro_user.provider.default_user');

        return $defaultUserProvider->getDefaultUser('oro_customer.default_customer_owner');
    }

    private function getGuestCustomerGroup(): ?CustomerGroup
    {
        /** @var CustomerUserRelationsProvider $customerUserRelationsProvider */
        $customerUserRelationsProvider = self::getContainer()
            ->get('oro_customer.provider.customer_user_relations_provider');

        return $customerUserRelationsProvider->getCustomerGroup();
    }

    private function assertGuestCustomer(CustomerUser $customerUser): void
    {
        $customerGroup = $this->getGuestCustomerGroup();
        $customerGroupId = null !== $customerGroup ? $customerGroup->getId() : null;

        $customer = $customerUser->getCustomer();
        self::assertSame(' ', $customer->getName());
        if (null === $customerGroupId) {
            self::assertNull($customer->getGroup());
        } else {
            self::assertSame($customerGroupId, $customer->getGroup()->getId());
        }
        self::assertSame($customerUser->getOrganization()->getId(), $customer->getOrganization()->getId());
        self::assertSame($customerUser->getOwner()->getId(), $customer->getOwner()->getId());
        self::assertNull($customer->getParent());
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'customerusers']);
        self::assertResponseCount(0, $response);
    }

    public function testGetListForExistingGuestCustomerUser(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout_min.yml');
        $guestCustomerUserId = (int)$this->getResourceId($response);

        $response = $this->cget(['entity' => 'customerusers']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerusers',
                        'id'   => (string)$guestCustomerUserId
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForGuestCustomerUser(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout_min.yml');
        $guestCustomerUserId = (int)$this->getResourceId($response);

        $response = $this->get(['entity' => 'customerusers', 'id' => (string)$guestCustomerUserId]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customerusers',
                    'id'   => (string)$guestCustomerUserId
                ]
            ],
            $response
        );
    }

    public function testTryToGetForNotGuestCustomerUser(): void
    {
        $response = $this->get(
            ['entity' => 'customerusers', 'id' => '<toString(@customer_user1->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $website = $this->getCurrentWebsite();
        $websiteId = $website->getId();
        $organizationId = $website->getOrganization()->getId();
        $roleId = $website->getDefaultRole()->getId();
        $owner = $this->getDefaultUser();
        $ownerId = null !== $owner ? $owner->getId() : null;

        $data = $this->getRequestData('create_customer_user_guest_checkout_min.yml');
        $response = $this->post(['entity' => 'customerusers'], $data);

        $responseContent = self::jsonToArray($response->getContent());
        $customerUserId = (int)$this->getResourceId($response);
        $expectedContent = $data;
        $expectedContent['data']['id'] = (string)$customerUserId;
        $expectedContent['data']['attributes']['firstName'] = null;
        $expectedContent['data']['attributes']['lastName'] = null;
        $expectedContent['data']['attributes']['middleName'] = null;
        $expectedContent['data']['attributes']['namePrefix'] = null;
        $expectedContent['data']['attributes']['nameSuffix'] = null;
        $expectedContent['data']['attributes']['birthday'] = null;
        $expectedContent['data']['attributes']['enabled'] = false;
        $expectedContent['data']['attributes']['confirmed'] = false;
        $expectedContent['data']['relationships']['customer']['data'] = ['type' => 'customers'];
        $expectedContent['data']['relationships']['addresses']['data'] = [];
        $expectedContent['data']['relationships']['userRoles']['data'] = [
            ['type' => 'customeruserroles', 'id' => (string)$roleId]
        ];
        $this->assertResponseContains($expectedContent, $response);

        $customerUser = $this->getEntityManager()->find(CustomerUser::class, $customerUserId);
        $customerUserData = $responseContent['data'];
        self::assertEquals($customerUserData['attributes']['email'], $customerUser->getEmail());
        self::assertEquals($customerUserData['attributes']['email'], $customerUser->getUsername());
        self::assertNull($customerUser->getFirstName());
        self::assertNull($customerUser->getLastName());
        self::assertNull($customerUser->getMiddleName());
        self::assertNull($customerUser->getNamePrefix());
        self::assertNull($customerUser->getNameSuffix());
        self::assertFalse($customerUser->isEnabled());
        self::assertFalse($customerUser->isConfirmed());
        self::assertNotNull($customerUser->getCustomer());
        self::assertCount(0, $customerUser->getAddresses());
        self::assertCount(1, $customerUser->getUserRoles());
        self::assertSame($roleId, $customerUser->getUserRoles()[0]->getId());
        self::assertTrue($customerUser->isGuest());
        self::assertSame($websiteId, $customerUser->getWebsite()->getId());
        self::assertSame($organizationId, $customerUser->getOrganization()->getId());
        self::assertSame($ownerId, $customerUser->getOwner()->getId());
        self::assertNotEmpty($customerUser->getPassword());

        $this->assertGuestCustomer($customerUser);

        $currentVisitor = $this->getCurrentVisitor();
        $visitor = $this->getEntityManager()->find(CustomerVisitor::class, $currentVisitor->getId());
        self::assertNotSame($currentVisitor, $visitor); // to be sure that the visitor is loaded from the database
        self::assertSame($customerUserId, $visitor->getCustomerUser()->getId());
    }

    public function testCreate(): void
    {
        $website = $this->getCurrentWebsite();
        $websiteId = $website->getId();
        $organizationId = $website->getOrganization()->getId();
        $roleId = $website->getDefaultRole()->getId();
        $owner = $this->getDefaultUser();
        $ownerId = null !== $owner ? $owner->getId() : null;

        $data = $this->getRequestData('create_customer_user_guest_checkout.yml');
        $response = $this->post(['entity' => 'customerusers'], $data);

        $responseContent = self::jsonToArray($response->getContent());
        $customerUserId = (int)$this->getResourceId($response);
        $addressId = (int)$responseContent['data']['relationships']['addresses']['data'][0]['id'];
        $expectedContent = $data;
        $expectedContent['data']['id'] = (string)$customerUserId;
        $expectedContent['data']['attributes']['middleName'] = null;
        $expectedContent['data']['attributes']['namePrefix'] = null;
        $expectedContent['data']['attributes']['nameSuffix'] = null;
        $expectedContent['data']['attributes']['birthday'] = null;
        $expectedContent['data']['attributes']['enabled'] = false;
        $expectedContent['data']['attributes']['confirmed'] = false;
        $expectedContent['data']['relationships']['customer']['data'] = ['type' => 'customers'];
        $expectedContent['data']['relationships']['addresses']['data'][0]['id'] = (string)$addressId;
        $expectedContent['included'][0]['id'] = (string)$addressId;
        $expectedContent['data']['relationships']['userRoles']['data'] = [
            ['type' => 'customeruserroles', 'id' => (string)$roleId]
        ];
        $this->assertResponseContains($expectedContent, $response);

        $customerUser = $this->getEntityManager()->find(CustomerUser::class, $customerUserId);
        $customerUserData = $responseContent['data'];
        self::assertEquals($customerUserData['attributes']['email'], $customerUser->getEmail());
        self::assertEquals($customerUserData['attributes']['email'], $customerUser->getUsername());
        self::assertEquals($customerUserData['attributes']['firstName'], $customerUser->getFirstName());
        self::assertEquals($customerUserData['attributes']['lastName'], $customerUser->getLastName());
        self::assertNull($customerUser->getMiddleName());
        self::assertNull($customerUser->getNamePrefix());
        self::assertNull($customerUser->getNameSuffix());
        self::assertFalse($customerUser->isEnabled());
        self::assertFalse($customerUser->isConfirmed());
        self::assertNotNull($customerUser->getCustomer());
        self::assertCount(1, $customerUser->getAddresses());
        self::assertSame($addressId, $customerUser->getAddresses()->first()->getId());
        self::assertCount(1, $customerUser->getUserRoles());
        self::assertSame($roleId, $customerUser->getUserRoles()[0]->getId());
        self::assertTrue($customerUser->isGuest());
        self::assertSame($websiteId, $customerUser->getWebsite()->getId());
        self::assertSame($organizationId, $customerUser->getOrganization()->getId());
        self::assertSame($ownerId, $customerUser->getOwner()->getId());
        self::assertNotEmpty($customerUser->getPassword());

        $this->assertGuestCustomer($customerUser);
        $address = $this->getEntityManager()->find(CustomerUserAddress::class, $addressId);
        $addressData = $responseContent['included'][0];
        self::assertEquals($addressData['attributes']['organization'], $address->getOrganization());
        self::assertEquals($addressData['attributes']['street'], $address->getStreet());
        self::assertEquals($addressData['attributes']['city'], $address->getCity());
        self::assertEquals($addressData['attributes']['postalCode'], $address->getPostalCode());
        self::assertEquals(
            $addressData['relationships']['country']['data']['id'],
            $address->getCountry()->getIso2Code()
        );
        self::assertEquals(
            $addressData['relationships']['region']['data']['id'],
            $address->getRegion()->getCombinedCode()
        );
        self::assertEquals($customerUserId, $address->getFrontendOwner()->getId());
    }

    public function testTryToCreateWithCustomer(): void
    {
        $submittedCustomerId = $this->getReference('customer')->getId();

        $data = $this->getRequestData('create_customer_user_guest_checkout_min.yml');
        $data['data']['relationships']['customer']['data'] = [
            'type' => 'customers',
            'id'   => (string)$submittedCustomerId
        ];
        $response = $this->post(['entity' => 'customerusers'], $data);

        $customerUserId = (int)$this->getResourceId($response);
        $expectedContent = $data;
        $expectedContent['data']['id'] = (string)$customerUserId;
        $expectedContent['data']['relationships']['customer']['data'] = ['type' => 'customers'];
        $this->assertResponseContains($expectedContent, $response);

        $customerUser = $this->getEntityManager()->find(CustomerUser::class, $customerUserId);
        self::assertNotNull($customerUser->getCustomer());
        self::assertNotSame($submittedCustomerId, $customerUser->getCustomer()->getId());
        $this->assertGuestCustomer($customerUser);
    }

    public function testTryToCreateWithRoles(): void
    {
        $roleId = $this->getCurrentWebsite()->getDefaultRole()->getId();

        $data = $this->getRequestData('create_customer_user_guest_checkout_min.yml');
        $data['data']['relationships']['userRoles']['data'] = [
            ['type' => 'customeruserroles', 'id' => '<toString(@admin->id)>']
        ];
        $response = $this->post(['entity' => 'customerusers'], $data);

        $customerUserId = (int)$this->getResourceId($response);
        $expectedContent = $data;
        $expectedContent['data']['id'] = (string)$customerUserId;
        $expectedContent['data']['relationships']['userRoles']['data'] = [
            ['type' => 'customeruserroles', 'id' => (string)$roleId]
        ];
        $this->assertResponseContains($expectedContent, $response);

        $customerUser = $this->getEntityManager()->find(CustomerUser::class, $customerUserId);
        self::assertNotNull($customerUser->getCustomer());
        self::assertSame($roleId, $customerUser->getUserRoles()[0]->getId());
    }

    public function testTryToCreateWithEnabledAndConfirmed(): void
    {
        $data = $this->getRequestData('create_customer_user_guest_checkout_min.yml');
        $data['data']['attributes']['enabled'] = true;
        $data['data']['attributes']['confirmed'] = true;
        $response = $this->post(['entity' => 'customerusers'], $data);

        $customerUserId = (int)$this->getResourceId($response);
        $expectedContent = $data;
        $expectedContent['data']['id'] = (string)$customerUserId;
        $expectedContent['data']['attributes']['enabled'] = false;
        $expectedContent['data']['attributes']['confirmed'] = false;
        $this->assertResponseContains($expectedContent, $response);

        $customerUser = $this->getEntityManager()->find(CustomerUser::class, $customerUserId);
        self::assertFalse($customerUser->isEnabled());
        self::assertFalse($customerUser->isConfirmed());
    }

    public function testTryToUpdate(): void
    {
        $customerUserId = $this->getReference('customer_user1')->getId();

        $response = $this->patch(
            ['entity' => 'customerusers', 'id' => (string)$customerUserId],
            [
                'data' => [
                    'type'       => 'customerusers',
                    'id'         => (string)$customerUserId,
                    'attributes' => [
                        'firstName' => 'Updated First Name'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to this type of entities.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDelete(): void
    {
        $customerUserId = $this->getReference('customer_user1')->getId();

        $response = $this->delete(
            ['entity' => 'customerusers', 'id' => (string)$customerUserId],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to this type of entities.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'customerusers'],
            ['filter[email]' => 'user1@example.com'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to this type of entities.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
