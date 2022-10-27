<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadPaymentTermData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CreateOrderTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml',
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

    public function testCreate()
    {
        $shipUntil = (new \DateTime('now + 10 day'))->format('Y-m-d');
        $data = $this->getRequestData('create_order.yml');
        $data['data']['attributes']['shipUntil'] = $shipUntil;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->getResponseData('create_order.yml');
        $responseContent['data']['attributes']['shipUntil'] = $shipUntil;
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithRequiredDataOnly()
    {
        $response = $this->post(
            ['entity' => 'orders'],
            'create_order_min.yml'
        );

        $responseContent = $this->updateResponseContent('create_order_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithCurrency()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['data']['attributes']['currency'] = 'EUR';

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->updateResponseContent('create_order_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithoutProductRelationshipButWithProductSku()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['productSku'] = '@product1->sku';
        unset($data['included'][0]['relationships']['product']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->updateResponseContent('create_order_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithCustomerUserAddresesAsOrderAddresses()
    {
        $response = $this->post(
            ['entity' => 'orders'],
            'create_order_customer_user_addresses.yml'
        );

        $responseContent = $this->updateResponseContent('create_order_customer_user_addresses.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithFilledBillingAddressData()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][1] = $this->getRequestData('order_address_data.yml');

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->getResponseData('create_order_min.yml');
        $responseContent['included'][1]['relationships']['customerAddress']['data'] = null;
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateEmpty()
    {
        $data = [
            'data' => [
                'type' => 'orders'
            ]
        ];

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
                ],
                [
                    'title'  => 'count constraint',
                    'detail' => 'Please add at least one Line Item',
                    'source' => ['pointer' => '/data/relationships/lineItems/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProduct()
    {
        $data = $this->getRequestData('create_order_min.yml');
        unset($data['included'][0]['relationships']['product']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'line item product constraint',
                'detail' => 'Please choose Product.',
                'source' => ['pointer' => '/included/0/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithFreeFormProduct()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['freeFormProduct'] = 'test';
        unset($data['included'][0]['relationships']['product']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'line item product constraint',
                'detail' => 'Please choose Product.',
                'source' => ['pointer' => '/included/0/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProductUnit()
    {
        $data = $this->getRequestData('create_order_min.yml');
        unset($data['included'][0]['relationships']['productUnit']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongProductUnit()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product2->id)>';
        $data['included'][0]['relationships']['productUnit']['data']['id'] = '<toString(@set->code)>';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotSellProductProductUnit()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product3->id)>';
        $data['included'][0]['relationships']['productUnit']['data']['id'] = '<toString(@set->code)>';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithFloatQuantityWhenPrecisionIsZero()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['quantity'] = 123.45;
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product2->id)>';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "item" is not valid.',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutQuantity()
    {
        $data = $this->getRequestData('create_order_min.yml');
        unset($data['included'][0]['attributes']['quantity']);
        if (!$data['included'][0]['attributes']) {
            unset($data['included'][0]['attributes']);
        }

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithNegativeQuantity()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['quantity'] = -1;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'greater than constraint',
                'detail' => 'This value should be greater than 0.',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWhenPaymentMethodWasNotFound()
    {
        $this->getReference('payment_term_rule')->setEnabled(false);
        $this->getEntityManager()->flush();

        $response = $this->post(
            ['entity' => 'orders'],
            'create_order_min.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'payment method constraint',
                'detail' => 'No payment methods are available, please contact us to complete the order submission.'
            ],
            $response
        );
    }

    public function testTryToCreateWithSubmittedPriceThatNotEqualsToCalculatedPrice()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['price'] = 9999;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'price match constraint',
                'detail' => 'The specified price must be equal to 1.01.',
                'source' => ['pointer' => '/included/0/attributes/price']
            ],
            $response
        );
    }

    public function testCreateWithSubmittedNullPrice()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['price'] = null;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][0]['id'] = 'new';
        $expectedData['data']['relationships']['billingAddress']['data']['id'] = 'new';
        $expectedData['data']['relationships']['shippingAddress']['data']['id'] = 'new';
        $expectedData['included'][0]['id'] = 'new';
        $expectedData['included'][0]['attributes']['price'] = '1.0100';
        $expectedData['included'][1]['id'] = 'new';
        $expectedData['included'][2]['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateWithSubmittedCurrencyThatNotEqualsToCalculatedCurrency()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['currency'] = 'EUR';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'currency match constraint',
                'detail' => 'The specified currency must be equal to "USD".',
                'source' => ['pointer' => '/included/0/attributes/currency']
            ],
            $response
        );
    }

    public function testCreateWithSubmittedNullCurrency()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['currency'] = null;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][0]['id'] = 'new';
        $expectedData['data']['relationships']['billingAddress']['data']['id'] = 'new';
        $expectedData['data']['relationships']['shippingAddress']['data']['id'] = 'new';
        $expectedData['included'][0]['id'] = 'new';
        $expectedData['included'][0]['attributes']['currency'] = 'USD';
        $expectedData['included'][1]['id'] = 'new';
        $expectedData['included'][2]['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateWithProductWithoutPrice()
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product3->id)>';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'price not found constraint',
                'detail' => 'No matching price found.',
                'source' => ['pointer' => '/included/0/attributes/price']
            ],
            $response
        );
    }

    public function testTryToCreateWithExistingBillingAddress()
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

    public function testTryToCreateWithExistingShippingAddress()
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

    public function testTryToCreateWhenBillingAddressHasBothCustomerAndCustomerUserAddress()
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

    public function testTryToCreateWhenShippingAddressHasBothCustomerAndCustomerUserAddress()
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

    public function testTryToCreateWhenBillingAddressHasCustomerAddressAndOtherAddressFields()
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

    public function testTryToCreateWhenShippingAddressHasCustomerAddressAndOtherAddressFields()
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

    public function testTryToCreateWhenBillingAddressHasCustomerUserAddressAndOtherAddressFields()
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

    public function testTryToCreateWhenShippingAddressHasCustomerUserAddressAndOtherAddressFields()
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

    public function testTryToCreateWithEmptyAddresses()
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

    public function testTryToCreateWithEmptyAddressesData()
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

    public function testTryToCreateWithNotAccessibleCustomerAddressAsOrderAddresses()
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

    public function testTryToCreateWithNotAccessibleCustomerUserAddressAsOrderAddresses()
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

    public function testTryToCreateWithNotExistingCustomerAddressAsOrderAddresses()
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

    public function testTryToCreateWithNotExistingCustomerUserAddressAsOrderAddresses()
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

    public function testTryToCreateWithCountryIncompatibleWithExistingRegionInBillingAddress()
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

    public function testTryToCreateWithRegionIncompatibleWithExistingCountryInBillingAddress()
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
