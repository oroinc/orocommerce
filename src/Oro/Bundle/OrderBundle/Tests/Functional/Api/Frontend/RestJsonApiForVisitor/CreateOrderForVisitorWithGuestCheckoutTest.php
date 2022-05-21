<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApiForVisitor;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CreateOrderForVisitorWithGuestCheckoutTest extends FrontendRestJsonApiTestCase
{
    use RolePermissionExtension;

    private const COMMON_REQUESTS_PATH = '@OroOrderBundle/Tests/Functional/Api/Frontend/RestJsonApi/requests/';

    /** @var bool */
    private $originalGuestCheckoutOptionValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml',
            LoadPaymentTermData::class
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

    protected function postFixtureLoad(): void
    {
        parent::postFixtureLoad();
        /** @var PaymentTermAssociationProvider $paymentTermAssociationProvider */
        $paymentTermAssociationProvider = self::getContainer()
            ->get('oro_payment_term.provider.payment_term_association');
        $paymentTermAssociationProvider->setPaymentTerm(
            $this->getGuestCustomerGroup(),
            $this->getReference('payment_term_net_10')
        );
        $this->getEntityManager()->flush();
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

    private function getCurrentWebsite(): Website
    {
        /** @var WebsiteManager $websiteManager */
        $websiteManager = self::getContainer()->get('oro_website.manager');

        return $websiteManager->getCurrentWebsite();
    }

    private function getGuestCustomerUserRole(): CustomerUserRole
    {
        return $this->getCurrentWebsite()->getGuestRole();
    }

    private function getGuestCustomerGroup(): ?CustomerGroup
    {
        /** @var CustomerUserRelationsProvider $customerUserRelationsProvider */
        $customerUserRelationsProvider = self::getContainer()
            ->get('oro_customer.provider.customer_user_relations_provider');

        return $customerUserRelationsProvider->getCustomerGroup();
    }

    private function addGuestCustomerUserToRequestData(array $data): array
    {
        $data['data']['relationships']['customerUser']['data'] = [
            'type' => 'customerusers',
            'id'   => 'guest'
        ];
        $data['included'][] = [
            'type'       => 'customerusers',
            'id'         => 'guest',
            'attributes' => [
                'email' => 'test2341@test.com'
            ]
        ];

        return $data;
    }

    public function testCreate(): void
    {
        $response = $this->post(['entity' => 'orders'], 'create_order_guest_checkout.yml');

        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateForExistingGuestCustomerUser(): void
    {
        // do the first request to create a guest customer user
        $response = $this->post(['entity' => 'orders'], 'create_order_guest_checkout.yml');
        $responseContent = self::jsonToArray($response->getContent());
        $customerUserId = (int)$responseContent['data']['relationships']['customerUser']['data']['id'];

        // do the second request with the guest customer user created by the first request
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3]);
        $response = $this->post(['entity' => 'orders'], $data);
        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        unset($responseContent['included'][3]);
        $this->assertResponseContains($responseContent, $response);

        self::assertSame(
            $customerUserId,
            (int)$responseContent['data']['relationships']['customerUser']['data']['id'],
            'The guest customer user should be reused'
        );
    }

    public function testTryToCreateWithAnotherCustomerUserWhenGuestCustomerUserDoesNotExist(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] =
            (string)$this->getReference('customer_user')->getId();
        unset($data['included'][3]);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'access granted constraint',
                'detail' => 'No access to the entity.',
                'source' => ['pointer' => '/data/relationships/customerUser/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithAnotherCustomerUserWhenGuestCustomerUserExists(): void
    {
        // do the first request to create a guest customer user
        $this->post(['entity' => 'orders'], 'create_order_guest_checkout.yml');

        // do the second request with the guest customer user created by the first request
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] =
            (string)$this->getReference('customer_user')->getId();
        unset($data['included'][3]);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'access granted constraint',
                'detail' => 'No access to the entity.',
                'source' => ['pointer' => '/data/relationships/customerUser/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutCustomerUser(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['data']['relationships']['customerUser'], $data['included'][3]);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/customer/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNullCustomerUser(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data'] = null;
        unset($data['included'][3]);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/customer/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutCustomerUserEmail(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['included'][3]['attributes']);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/included/3/attributes/email']
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidCustomerUserEmail(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][3]['attributes']['email'] = 'invalid email';
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'email constraint',
                'detail' => 'This value is not a valid email address.',
                'source' => ['pointer' => '/included/3/attributes/email']
            ],
            $response
        );
    }

    public function testCreateWithCustomerThatIsReadonlyFieldAndShouldIgnored(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customer'] = ['data' => ['type' => 'customers', 'id' => '1']];
        $response = $this->post(['entity' => 'orders'], $data);

        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithoutProductRelationshipButWithProductSku(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['attributes']['productSku'] = '@product1->sku';
        unset($data['included'][0]['relationships']['product']);
        $response = $this->post(['entity' => 'orders'], $data);

        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateWithoutProduct(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['included'][0]['relationships']['product']);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'line item product constraint',
                'detail' => 'Please choose Product.',
                'source' => ['pointer' => '/included/0/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithFreeFormProduct(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['attributes']['freeFormProduct'] = 'test';
        unset($data['included'][0]['relationships']['product']);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'line item product constraint',
                'detail' => 'Please choose Product.',
                'source' => ['pointer' => '/included/0/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProductUnit(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['included'][0]['relationships']['productUnit']);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongProductUnit(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product2->id)>';
        $data['included'][0]['relationships']['productUnit']['data']['id'] = '<toString(@set->code)>';
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotSellProductProductUnit(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product3->id)>';
        $data['included'][0]['relationships']['productUnit']['data']['id'] = '<toString(@set->code)>';
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithFloatQuantityWhenPrecisionIsZero(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['attributes']['quantity'] = 123.45;
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product2->id)>';
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "item" is not valid.',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutQuantity(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['included'][0]['attributes']);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithNegativeQuantity(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['attributes']['quantity'] = -1;
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'greater than constraint',
                'detail' => 'This value should be greater than 0.',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWhenPaymentMethodWasNotFound(): void
    {
        $this->getReference('payment_term_rule')->setEnabled(false);
        $this->getEntityManager()->flush();

        $response = $this->post(['entity' => 'orders'], 'create_order_guest_checkout.yml', [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'payment method constraint',
                'detail' => 'No payment methods are available, please contact us to complete the order submission.'
            ],
            $response
        );
    }

    public function testCreateWithCurrency(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['attributes']['currency'] = 'EUR';
        $response = $this->post(['entity' => 'orders'], $data);

        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithSubmittedNullCurrency(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['attributes']['currency'] = null;
        $response = $this->post(['entity' => 'orders'], $data);

        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateWithSubmittedCurrencyThatNotEqualsToCalculatedCurrency(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['attributes']['currency'] = 'EUR';
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'currency match constraint',
                'detail' => 'The specified currency must be equal to "USD".',
                'source' => ['pointer' => '/included/0/attributes/currency']
            ],
            $response
        );
    }

    public function testCreateWithSubmittedNullPrice(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['attributes']['price'] = null;
        $response = $this->post(['entity' => 'orders'], $data);

        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateWithSubmittedPriceThatNotEqualsToCalculatedPrice(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['attributes']['price'] = 9999;
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'price match constraint',
                'detail' => 'The specified price must be equal to 1.01.',
                'source' => ['pointer' => '/included/0/attributes/price']
            ],
            $response
        );
    }

    public function testTryToCreateWithProductWithoutPrice(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product3->id)>';
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'price not found constraint',
                'detail' => 'No matching price found.',
                'source' => ['pointer' => '/included/0/attributes/price']
            ],
            $response
        );
    }

    public function testTryToCreateWhenBillingAddressHasBothCustomerAndCustomerUserAddress(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][1]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => '<toString(@customer_user_address->id)>'
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'order address constraint',
                'detail' => 'Only order address fields, a customer user address or a customer address can be set.',
                'source' => ['pointer' => '/included/1']
            ],
            $response
        );
    }

    public function testTryToCreateWhenShippingAddressHasBothCustomerAndCustomerUserAddress(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][2]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => '<toString(@customer_user_address->id)>'
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'order address constraint',
                'detail' => 'Only order address fields, a customer user address or a customer address can be set.',
                'source' => ['pointer' => '/included/2']
            ],
            $response
        );
    }

    public function testTryToCreateWhenBillingAddressHasCustomerUserAddressAndOtherAddressFields(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][1]['attributes']['label'] = 'Address 1';
        unset($data['included'][1]['relationships']['customerAddress']);
        $data['included'][1]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => '<toString(@customer_user_address->id)>'
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'order address constraint',
                'detail' => 'Only order address fields, a customer user address or a customer address can be set.',
                'source' => ['pointer' => '/included/1']
            ],
            $response
        );
    }

    public function testTryToCreateWhenShippingAddressHasCustomerUserAddressAndOtherAddressFields(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['included'][2]['attributes']['label'] = 'Address 1';
        unset($data['included'][2]['relationships']['customerAddress']);
        $data['included'][2]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => '<toString(@customer_user_address->id)>'
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'order address constraint',
                'detail' => 'Only order address fields, a customer user address or a customer address can be set.',
                'source' => ['pointer' => '/included/2']
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyAddresses(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['billingAddress']['data'] = null;
        $data['data']['relationships']['shippingAddress']['data'] = null;
        unset($data['included'][1], $data['included'][2]);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/billingAddress/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/shippingAddress/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyAddressesData(): void
    {
        $data = $this->getRequestData('create_order_guest_checkout.yml');
        unset($data['included'][1]['relationships'], $data['included'][2]['relationships']);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/2/relationships/country/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/1/relationships/country/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithCustomerUserAddressesAsOrderAddresses(): void
    {
        $data = $this->getRequestData(self::COMMON_REQUESTS_PATH . 'create_order_customer_user_addresses.yml');
        $data = $this->addGuestCustomerUserToRequestData($data);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseContainsValidationErrors(
            [
                [
                    'title'  => 'customer or user address granted constraint',
                    'detail' => 'It is not allowed to use this address for the order.',
                    'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
                ],
                [
                    'title'  => 'customer or user address granted constraint',
                    'detail' => 'It is not allowed to use this address for the order.',
                    'source' => ['pointer' => '/included/2/relationships/customerUserAddress/data']
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithNotAccessibleCustomerUserAddressAsOrderAddresses(): void
    {
        $data = $this->getRequestData(self::COMMON_REQUESTS_PATH . 'create_order_customer_user_addresses.yml');
        $data['included'][1]['relationships']['customerUserAddress']['data']['id'] =
            (string)$this->getReference('customer_user_address')->getId();
        $data = $this->addGuestCustomerUserToRequestData($data);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'customer or user address granted constraint',
                    'detail' => 'It is not allowed to use this address for the order.',
                    'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
                ],
                [
                    'title'  => 'customer or user address granted constraint',
                    'detail' => 'It is not allowed to use this address for the order.',
                    'source' => ['pointer' => '/included/2/relationships/customerUserAddress/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithNotExistingCustomerUserAddressAsOrderAddresses(): void
    {
        $data = $this->getRequestData(self::COMMON_REQUESTS_PATH . 'create_order_customer_user_addresses.yml');
        $data['included'][1]['relationships']['customerUserAddress']['data']['id'] = '1000000';
        $data = $this->addGuestCustomerUserToRequestData($data);
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'The entity does not exist.',
                'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotExistingCustomerUserAddressAsOrderAddressesAndNoCustomerUser(): void
    {
        $data = $this->getRequestData(self::COMMON_REQUESTS_PATH . 'create_order_customer_user_addresses.yml');
        $data['included'][1]['relationships']['customerUserAddress']['data']['id'] = '1000000';
        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'The entity does not exist.',
                'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithBillingAddressOfExistingGuestCustomerUserWhenItIsDisabled(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $customerUserData = self::jsonToArray($response->getContent());
        $customerUserId = (int)$customerUserData['data']['id'];
        $customerUserAddressId = (int)$customerUserData['data']['relationships']['addresses']['data'][0]['id'];

        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3], $data['included'][1]['attributes']);
        $data['included'][1]['relationships'] = [
            'customerUserAddress' => [
                'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
            ]
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'customer or user address granted constraint',
                'detail' => 'It is not allowed to use this address for the order.',
                'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithShippingAddressOfExistingGuestCustomerUserWhenItIsDisabled(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $customerUserData = self::jsonToArray($response->getContent());
        $customerUserId = (int)$customerUserData['data']['id'];
        $customerUserAddressId = (int)$customerUserData['data']['relationships']['addresses']['data'][0]['id'];

        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3], $data['included'][2]['attributes']);
        $data['included'][2]['relationships'] = [
            'customerUserAddress' => [
                'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
            ]
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'customer or user address granted constraint',
                'detail' => 'It is not allowed to use this address for the order.',
                'source' => ['pointer' => '/included/2/relationships/customerUserAddress/data']
            ],
            $response
        );
    }

    public function testCreateWithBillingAddressOfExistingGuestCustomerUserWhenItIsEnabled(): void
    {
        $guestRoleName = $this->getGuestCustomerUserRole()->getRole();
        $this->updateRolePermissionForAction(
            $guestRoleName,
            'oro_order_address_billing_customer_user_use_any',
            true
        );
        $this->updateRolePermission(
            $guestRoleName,
            CustomerUserAddress::class,
            AccessLevel::BASIC_LEVEL
        );

        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $customerUserData = self::jsonToArray($response->getContent());
        $customerUserAddressData = $customerUserData['included'][0];
        $customerUserId = (int)$customerUserData['data']['id'];
        $customerUserAddressId = (int)$customerUserData['data']['relationships']['addresses']['data'][0]['id'];

        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3], $data['included'][1]['attributes']);
        $data['included'][1]['relationships'] = [
            'customerUserAddress' => [
                'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
            ]
        ];
        $response = $this->post(['entity' => 'orders'], $data);
        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        unset($responseContent['included'][3]);
        $responseContent['included'][1]['attributes'] = [
            'phone'        => $customerUserAddressData['attributes']['phone'],
            'label'        => $customerUserAddressData['attributes']['label'],
            'street'       => $customerUserAddressData['attributes']['street'],
            'street2'      => $customerUserAddressData['attributes']['street2'],
            'city'         => $customerUserAddressData['attributes']['city'],
            'postalCode'   => $customerUserAddressData['attributes']['postalCode'],
            'organization' => $customerUserAddressData['attributes']['organization'],
            'customRegion' => $customerUserAddressData['attributes']['customRegion'],
            'namePrefix'   => $customerUserAddressData['attributes']['namePrefix'],
            'firstName'    => $customerUserAddressData['attributes']['firstName'],
            'middleName'   => $customerUserAddressData['attributes']['middleName'],
            'lastName'     => $customerUserAddressData['attributes']['lastName'],
            'nameSuffix'   => $customerUserAddressData['attributes']['nameSuffix']
        ];
        $responseContent['included'][1]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => (string)$customerUserAddressId
        ];
        $responseContent['included'][1]['relationships']['country']['data'] =
            $customerUserAddressData['relationships']['country']['data'];
        $responseContent['included'][1]['relationships']['region']['data'] =
            $customerUserAddressData['relationships']['region']['data'];
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithShippingAddressOfExistingGuestCustomerUserWhenItIsEnabled(): void
    {
        $guestRoleName = $this->getGuestCustomerUserRole()->getRole();
        $this->updateRolePermissionForAction(
            $guestRoleName,
            'oro_order_address_shipping_customer_user_use_any',
            true
        );
        $this->updateRolePermission(
            $guestRoleName,
            CustomerUserAddress::class,
            AccessLevel::BASIC_LEVEL
        );

        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $customerUserData = self::jsonToArray($response->getContent());
        $customerUserAddressData = $customerUserData['included'][0];
        $customerUserId = (int)$customerUserData['data']['id'];
        $customerUserAddressId = (int)$customerUserData['data']['relationships']['addresses']['data'][0]['id'];

        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3], $data['included'][2]['attributes']);
        $data['included'][2]['relationships'] = [
            'customerUserAddress' => [
                'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
            ]
        ];
        $response = $this->post(['entity' => 'orders'], $data);
        $responseContent = $this->updateResponseContent('create_order_guest_checkout.yml', $response);
        unset($responseContent['included'][3]);
        $responseContent['included'][2]['attributes'] = [
            'phone'        => $customerUserAddressData['attributes']['phone'],
            'label'        => $customerUserAddressData['attributes']['label'],
            'street'       => $customerUserAddressData['attributes']['street'],
            'street2'      => $customerUserAddressData['attributes']['street2'],
            'city'         => $customerUserAddressData['attributes']['city'],
            'postalCode'   => $customerUserAddressData['attributes']['postalCode'],
            'organization' => $customerUserAddressData['attributes']['organization'],
            'customRegion' => $customerUserAddressData['attributes']['customRegion'],
            'namePrefix'   => $customerUserAddressData['attributes']['namePrefix'],
            'firstName'    => $customerUserAddressData['attributes']['firstName'],
            'middleName'   => $customerUserAddressData['attributes']['middleName'],
            'lastName'     => $customerUserAddressData['attributes']['lastName'],
            'nameSuffix'   => $customerUserAddressData['attributes']['nameSuffix']
        ];
        $responseContent['included'][2]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => (string)$customerUserAddressId
        ];
        $responseContent['included'][2]['relationships']['country']['data'] =
            $customerUserAddressData['relationships']['country']['data'];
        $responseContent['included'][2]['relationships']['region']['data'] =
            $customerUserAddressData['relationships']['region']['data'];
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateWithNotAccessibleBillingAddrWhenUsingExistingGuestCustomerUserAddrDisabled(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $customerUserData = self::jsonToArray($response->getContent());
        $customerUserId = (int)$customerUserData['data']['id'];
        $customerUserAddressId = $this->getReference('customer_user_address')->getId();

        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3], $data['included'][1]['attributes']);
        $data['included'][1]['relationships'] = [
            'customerUserAddress' => [
                'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
            ]
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'customer or user address granted constraint',
                'detail' => 'It is not allowed to use this address for the order.',
                'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotAccessibleShippingAddrWhenUsingExistingGuestCustomerUserAddrDisabled(): void
    {
        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $customerUserData = self::jsonToArray($response->getContent());
        $customerUserId = (int)$customerUserData['data']['id'];
        $customerUserAddressId = $this->getReference('customer_user_address')->getId();

        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3], $data['included'][2]['attributes']);
        $data['included'][2]['relationships'] = [
            'customerUserAddress' => [
                'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
            ]
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'customer or user address granted constraint',
                'detail' => 'It is not allowed to use this address for the order.',
                'source' => ['pointer' => '/included/2/relationships/customerUserAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotAccessibleBillingAddrWhenUsingExistingGuestCustomerUserAddrEnabled(): void
    {
        $guestRoleName = $this->getGuestCustomerUserRole()->getRole();
        $this->updateRolePermissionForAction(
            $guestRoleName,
            'oro_order_address_billing_customer_user_use_any',
            true
        );
        $this->updateRolePermission(
            $guestRoleName,
            CustomerUserAddress::class,
            AccessLevel::BASIC_LEVEL
        );

        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $customerUserData = self::jsonToArray($response->getContent());
        $customerUserId = (int)$customerUserData['data']['id'];
        $customerUserAddressId = $this->getReference('customer_user_address')->getId();

        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3], $data['included'][1]['attributes']);
        $data['included'][1]['relationships'] = [
            'customerUserAddress' => [
                'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
            ]
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'customer or user address granted constraint',
                'detail' => 'It is not allowed to use this address for the order.',
                'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotAccessibleShippingAddrWhenUsingExistingGuestCustomerUserAddrEnabled(): void
    {
        $guestRoleName = $this->getGuestCustomerUserRole()->getRole();
        $this->updateRolePermissionForAction(
            $guestRoleName,
            'oro_order_address_billing_customer_user_use_any',
            true
        );
        $this->updateRolePermission(
            $guestRoleName,
            CustomerUserAddress::class,
            AccessLevel::BASIC_LEVEL
        );

        $response = $this->post(['entity' => 'customerusers'], 'create_customer_user_guest_checkout.yml');
        $customerUserData = self::jsonToArray($response->getContent());
        $customerUserId = (int)$customerUserData['data']['id'];
        $customerUserAddressId = $this->getReference('customer_user_address')->getId();

        $data = $this->getRequestData('create_order_guest_checkout.yml');
        $data['data']['relationships']['customerUser']['data']['id'] = (string)$customerUserId;
        unset($data['included'][3], $data['included'][2]['attributes']);
        $data['included'][2]['relationships'] = [
            'customerUserAddress' => [
                'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
            ]
        ];
        $response = $this->post(['entity' => 'orders'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'customer or user address granted constraint',
                'detail' => 'It is not allowed to use this address for the order.',
                'source' => ['pointer' => '/included/2/relationships/customerUserAddress/data']
            ],
            $response
        );
    }
}
