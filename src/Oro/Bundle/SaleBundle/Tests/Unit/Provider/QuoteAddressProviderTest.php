<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Tests\Unit\Provider\AbstractQuoteAddressProviderTest;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;
use Oro\Bundle\UserBundle\Entity\User;

class QuoteAddressProviderTest extends AbstractQuoteAddressProviderTest
{
    /** @var QuoteAddressProvider */
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new QuoteAddressProvider(
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
        $this->expectExceptionMessage('Unknown type "test", known types are: shipping');

        $this->provider->getCustomerAddresses(new Customer(), 'test');
    }

    public function testGetCustomerUserAddressesUnsupportedType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type "test", known types are: shipping');

        $this->provider->getCustomerUserAddresses(new CustomerUser(), 'test');
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
                    'oro_quote_address_shipping_customer_user_use_any' => false,
                    'oro_quote_address_shipping_customer_user_use_default' => false,
                ],
                null,
                [],
                new CustomerUser()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_customer_user_use_any' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new CustomerUser()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_customer_user_use_any' => false,
                    'oro_quote_address_shipping_customer_user_use_default' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new CustomerUser()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_customer_user_use_any_backend' => false,
                    'oro_quote_address_shipping_customer_user_use_default_backend' => false,
                ],
                null,
                [],
                new User()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_customer_user_use_any_backend' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new User()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_customer_user_use_any_backend' => false,
                    'oro_quote_address_shipping_customer_user_use_default_backend' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new User()
            ]
        ];
    }

    /**
     * @return array
     */
    public function customerAddressPermissions()
    {
        return [
            ['shipping', 'oro_quote_address_shipping_customer_use_any', new CustomerUser()],
            ['shipping', 'oro_quote_address_shipping_customer_use_any_backend', new User()],
        ];
    }
}
