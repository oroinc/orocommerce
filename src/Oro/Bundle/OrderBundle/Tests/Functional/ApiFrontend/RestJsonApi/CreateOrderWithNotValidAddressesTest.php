<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CreateOrderWithNotValidAddressesTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
            LoadPaymentTermData::class
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($this->getReference('customer'), $this->getReference('payment_term_net_10'));
        $this->getEntityManager()->flush();
    }

    public function testTryToCreateWithExistingBillingAddress(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['data']['relationships']['billingAddress']['data']['id'] = '<toString(@order1_billing_address->id)>';
        unset($data['included'][1]);
        $data['included'] = array_values($data['included']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'new address constraint',
                'detail' => 'An existing address cannot be used.',
                'source' => ['pointer' => '/data/relationships/billingAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithExistingShippingAddress(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['data']['relationships']['shippingAddress']['data']['id'] = '<toString(@order1_shipping_address->id)>';
        unset($data['included'][2]);
        $data['included'] = array_values($data['included']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'new address constraint',
                'detail' => 'An existing address cannot be used.',
                'source' => ['pointer' => '/data/relationships/shippingAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWhenBillingAddressHasBothCustomerAndCustomerUserAddress(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][1]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => '<toString(@customer_user_address->id)>'
        ];

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

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
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][2]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => '<toString(@customer_user_address->id)>'
        ];

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'order address constraint',
                'detail' => 'Only order address fields, a customer user address or a customer address can be set.',
                'source' => ['pointer' => '/included/2']
            ],
            $response
        );
    }

    public function testTryToCreateWhenBillingAddressHasCustomerAddressAndOtherAddressFields(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][1]['attributes']['label'] = 'Address 1';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'order address constraint',
                'detail' => 'Only order address fields, a customer user address or a customer address can be set.',
                'source' => ['pointer' => '/included/1']
            ],
            $response
        );
    }

    public function testTryToCreateWhenShippingAddressHasCustomerAddressAndOtherAddressFields(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][2]['attributes']['label'] = 'Address 1';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

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
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][1]['attributes']['label'] = 'Address 1';
        unset($data['included'][1]['relationships']['customerAddress']);
        $data['included'][1]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => '<toString(@customer_user_address->id)>'
        ];

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

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
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][2]['attributes']['label'] = 'Address 1';
        unset($data['included'][2]['relationships']['customerAddress']);
        $data['included'][2]['relationships']['customerUserAddress']['data'] = [
            'type' => 'customeruseraddresses',
            'id'   => '<toString(@customer_user_address->id)>'
        ];

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

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
        $data = $this->getRequestData('create_order_min.yml');
        $data['data']['relationships']['billingAddress']['data'] = null;
        $data['data']['relationships']['shippingAddress']['data'] = null;
        unset($data['included'][1], $data['included'][2]);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

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
        $data = $this->getRequestData('create_order_min.yml');
        unset($data['included'][1]['relationships'], $data['included'][2]['relationships']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            $this->getResponseData('empty_addresses_errors.yml'),
            $response
        );
    }

    public function testTryToCreateWithNotAccessibleCustomerAddressAsOrderAddresses(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][1]['relationships']['customerAddress']['data']['id'] =
            (string)$this->getReference('another_customer_address')->getId();

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'customer or user address granted constraint',
                    'detail' => 'It is not allowed to use this address for the order.',
                    'source' => ['pointer' => '/included/1/relationships/customerAddress/data']
                ],
                [
                    'title'  => 'access granted constraint',
                    'detail' => 'The "VIEW" permission is denied for the related resource.',
                    'source' => ['pointer' => '/included/1/relationships/customerAddress/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithNotAccessibleCustomerUserAddressAsOrderAddresses(): void
    {
        $data = $this->getRequestData('create_order_customer_user_addresses.yml');
        $data['included'][1]['relationships']['customerUserAddress']['data']['id'] =
            (string)$this->getReference('another_customer_user_address')->getId();

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'customer or user address granted constraint',
                    'detail' => 'It is not allowed to use this address for the order.',
                    'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
                ],
                [
                    'title'  => 'access granted constraint',
                    'detail' => 'The "VIEW" permission is denied for the related resource.',
                    'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithNotExistingCustomerAddressAsOrderAddresses(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][1]['relationships']['customerAddress']['data']['id'] = '10000000';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'The entity does not exist.',
                'source' => ['pointer' => '/included/1/relationships/customerAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotExistingCustomerUserAddressAsOrderAddresses(): void
    {
        $data = $this->getRequestData('create_order_customer_user_addresses.yml');
        $data['included'][1]['relationships']['customerUserAddress']['data']['id'] = '1000000';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'The entity does not exist.',
                'source' => ['pointer' => '/included/1/relationships/customerUserAddress/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithCountryIncompatibleWithExistingRegionInBillingAddress(): void
    {
        $countryId = $this->getEntityManager()->find(Country::class, 'MX')->getIso2Code();
        $data = $this->getRequestData('create_order_min.yml');
        $addressData = $this->getRequestData('order_address_data.yml');
        $addressData['relationships']['country']['data']['id'] = $countryId;
        $data['included'][1] = $addressData;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'valid region constraint',
                    'detail' => 'Region California does not belong to country Mexico',
                    'source' => ['pointer' => '/data/relationships/billingAddress/data']
                ],
                [
                    'title'  => 'valid region constraint',
                    'detail' => 'Region California does not belong to country Mexico',
                    'source' => ['pointer' => '/included/1']
                ],
            ],
            $response
        );
    }

    public function testTryToCreateWithRegionIncompatibleWithExistingCountryInBillingAddress(): void
    {
        $regionId = $this->getEntityManager()->find(Region::class, 'MX-GUA')->getCombinedCode();
        $data = $this->getRequestData('create_order_min.yml');
        $addressData = $this->getRequestData('order_address_data.yml');
        $addressData['relationships']['region']['data']['id'] = $regionId;
        $data['included'][1] = $addressData;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'valid region constraint',
                    'detail' => 'Region Guanajuato does not belong to country United States',
                    'source' => ['pointer' => '/data/relationships/billingAddress/data']
                ],
                [
                    'title'  => 'valid region constraint',
                    'detail' => 'Region Guanajuato does not belong to country United States',
                    'source' => ['pointer' => '/included/1']
                ],
            ],
            $response
        );
    }
}
