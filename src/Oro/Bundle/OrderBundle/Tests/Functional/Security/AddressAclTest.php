<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Security;

use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class AddressAclTest extends AbstractAddressAclTest
{
    private const BILLING_ADDRESS = 'billingAddress';
    private const SHIPPING_ADDRESS = 'shippingAddress';

    private const COMPANY_BILLING_DEFAULT_BILLING = '2413 Capitol Avenue, ROMNEY IN US 47981';
    private const COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING = '1215 Caldwell Road, ROCHESTER NY US 14608';
    private const COMPANY_BILLING_SHIPPING_ADDRESS = '722 Harvest Lane, SEDALIA MO US 65301';

    private const USER_BILLING_DEFAULT_BILLING = '2413 Capitol Avenue, ROMNEY IN US 47981';
    private const USER_BILLING_SHIPPING_DEFAULT_SHIPPING = '1215 Caldwell Road, ROCHESTER NY US 14608';
    private const USER_BILLING_SHIPPING_ADDRESS = '722 Harvest Lane, SEDALIA MO US 65301';

    private string $formName = 'oro_order_type';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadCustomers::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserData::class,
            LoadCustomerUserAddresses::class,
            LoadOrders::class
        ]);
    }

    /**
     * @dataProvider checkShippingAddressesDataProvider
     */
    public function testCheckShippingAddresses(array $permissions, array $capabilities, array $expected)
    {
        $this->setEntityPermissions(CustomerAddress::class, $permissions['customerEntityPermissions']);
        $this->setEntityPermissions(CustomerUserAddress::class, $permissions['customerUserEntityPermissions']);

        foreach ($capabilities as $capabilityId => $value) {
            $this->setActionPermissions($capabilityId, $value);
        }

        /** @var Order $order */
        $order = $this->getReference('simple_order');

        $crawler = $this->client->request('GET', $this->getUrl('oro_order_update', ['id' => $order->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if (!empty($expected)) {
            // Check shipping addresses
            self::assertStringContainsString('Shipping Address', $crawler->filter('.navbar-static')->html());
            $this->checkAddresses($crawler, $this->formName, self::SHIPPING_ADDRESS, $expected);
        } else {
            self::assertStringNotContainsString('Shipping Address', $crawler->filter('.navbar-static')->html());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function checkShippingAddressesDataProvider(): array
    {
        return [
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => true,
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [],
                    'manually' => true
                ],
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [],
                    'manually' => true
                ],
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [],
                    'customerUser' => [],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => false,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => false,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_shipping_customer_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_any_backend' => true,
                    'oro_order_address_shipping_customer_user_use_default_backend' => true,
                    'oro_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ]
        ];
    }

    /**
     * @dataProvider checkBillingAddressesDataProvider
     */
    public function testCheckBillingAddresses(array $permissions, array $capabilities, array $expected)
    {
        $this->setEntityPermissions(CustomerAddress::class, $permissions['customerEntityPermissions']);
        $this->setEntityPermissions(CustomerUserAddress::class, $permissions['customerUserEntityPermissions']);

        foreach ($capabilities as $capabilityId => $value) {
            $this->setActionPermissions($capabilityId, $value);
        }

        /** @var Order $order */
        $order = $this->getReference('simple_order');

        $crawler = $this->client->request('GET', $this->getUrl('oro_order_update', ['id' => $order->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if (!empty($expected)) {
            // Check billing addresses
            self::assertStringContainsString('Billing Address', $crawler->filter('.navbar-static')->html());
            $this->checkAddresses($crawler, $this->formName, self::BILLING_ADDRESS, $expected);
        } else {
            self::assertStringNotContainsString('Billing Address', $crawler->filter('.navbar-static')->html());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function checkBillingAddressesDataProvider(): array
    {
        return [
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => true,
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [],
                    'manually' => true
                ],
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [],
                    'manually' => true
                ],
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [],
                    'customerUser' => [],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => false,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => false,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'customerEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'customerUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_order_address_billing_customer_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_any_backend' => true,
                    'oro_order_address_billing_customer_user_use_default_backend' => true,
                    'oro_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'customer' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'customerUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ]
        ];
    }
}
