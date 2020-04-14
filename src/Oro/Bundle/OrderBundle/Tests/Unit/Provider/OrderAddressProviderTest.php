<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;

class OrderAddressProviderTest extends AbstractQuoteAddressProviderTest
{
    /**
     * @var OrderAddressProvider
     */
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new OrderAddressProvider(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->registry,
            $this->aclHelper,
            $this->customerAddressClass,
            $this->customerUserAddressClass
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown type "test", known types are: billing, shipping
     */
    public function testGetCustomerAddressesUnsupportedType()
    {
        $this->provider->getCustomerAddresses(new Customer(), 'test');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown type "test", known types are: billing, shipping
     */
    public function testGetCustomerUserAddressesUnsupportedType()
    {
        $this->provider->getCustomerUserAddresses(new CustomerUser(), 'test');
    }

    /**
     * @return array
     */
    public function customerAddressPermissions()
    {
        return [
            ['shipping', 'oro_order_address_shipping_customer_use_any', new CustomerUser()],
            ['shipping', 'oro_order_address_shipping_customer_use_any_backend', new \stdClass()],
            ['billing', 'oro_order_address_billing_customer_use_any', new CustomerUser()],
            ['billing', 'oro_order_address_billing_customer_use_any_backend', new \stdClass()],
        ];
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function customerUserAddressPermissions()
    {
        return [
            [
                'shipping',
                [
                    'oro_order_address_shipping_customer_user_use_any' => false,
                    'oro_order_address_shipping_customer_user_use_default' => false,
                ],
                null,
                [],
                new CustomerUser()
            ],
            [
                'shipping',
                [
                    'oro_order_address_shipping_customer_user_use_any' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new CustomerUser()
            ],
            [
                'shipping',
                [
                    'oro_order_address_shipping_customer_user_use_any' => false,
                    'oro_order_address_shipping_customer_user_use_default' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new CustomerUser()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any' => false,
                    'oro_order_address_billing_customer_user_use_default' => false,
                ],
                null,
                [],
                new CustomerUser()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new CustomerUser()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any' => false,
                    'oro_order_address_billing_customer_user_use_default' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new CustomerUser()
            ],
            [
                'shipping',
                [
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                ],
                null,
                [],
                new \stdClass()
            ],
            [
                'shipping',
                [
                    'oro_order_address_shipping_customer_user_use_any_backend' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new \stdClass()
            ],
            [
                'shipping',
                [
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new \stdClass()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                ],
                null,
                [],
                new \stdClass()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any_backend' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new \stdClass()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new \stdClass()
            ]
        ];
    }
}
