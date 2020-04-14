<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Tests\Unit\Provider\AbstractQuoteAddressProviderTest;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;

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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown type "test", known types are: shipping
     */
    public function testGetCustomerAddressesUnsupportedType()
    {
        $this->provider->getCustomerAddresses(new Customer(), 'test');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown type "test", known types are: shipping
     */
    public function testGetCustomerUserAddressesUnsupportedType()
    {
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
                new \stdClass()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_customer_user_use_any_backend' => true
                ],
                'getAddressesByType',
                [new CustomerUserAddress()],
                new \stdClass()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_customer_user_use_any_backend' => false,
                    'oro_quote_address_shipping_customer_user_use_default_backend' => true
                ],
                'getDefaultAddressesByType',
                [new CustomerUserAddress()],
                new \stdClass()
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
            ['shipping', 'oro_quote_address_shipping_customer_use_any_backend', new \stdClass()],
        ];
    }
}
