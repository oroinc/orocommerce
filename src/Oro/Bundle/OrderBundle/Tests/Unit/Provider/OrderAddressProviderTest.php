<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\UserBundle\Entity\User;

class OrderAddressProviderTest extends AbstractQuoteAddressProviderTest
{
    /** @var OrderAddressProvider */
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

    public function testGetCustomerAddressesUnsupportedType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type "test", known types are: billing, shipping');

        $this->provider->getCustomerAddresses(new Customer(), 'test');
    }

    public function testGetCustomerUserAddressesUnsupportedType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type "test", known types are: billing, shipping');

        $this->provider->getCustomerUserAddresses(new CustomerUser(), 'test');
    }

    public function customerAddressPermissions(): array
    {
        return [
            ['shipping', 'oro_order_address_shipping_customer_use_any', new CustomerUser()],
            ['shipping', 'oro_order_address_shipping_customer_use_any_backend', new User()],
            ['billing', 'oro_order_address_billing_customer_use_any', new CustomerUser()],
            ['billing', 'oro_order_address_billing_customer_use_any_backend', new User()],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function customerUserAddressPermissions(): array
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
                new User()
            ],
            [
                'shipping',
                [
                    'oro_order_address_shipping_customer_user_use_any_backend' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new User()
            ],
            [
                'shipping',
                [
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new User()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                ],
                null,
                [],
                new User()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any_backend' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new User()
            ],
            [
                'billing',
                [
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new User()
            ]
        ];
    }
}
