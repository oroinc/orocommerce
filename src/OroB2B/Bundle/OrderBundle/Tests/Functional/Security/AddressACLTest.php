<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Security;

use Symfony\Component\DomCrawler\Crawler;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\OrderBundle\Entity\Order;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class AddressACLTest extends WebTestCase
{
    const BILLING_ADDRESS = 'billingAddress';
    const SHIPPING_ADDRESS = 'shippingAddress';

    const COMPANY_BILLING_DEFAULT_BILLING = '2413 Capitol Avenue, ROMNEY IN US 47981';
    const COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING = '1215 Caldwell Road, ROCHESTER NY US 14608';
    const COMPANY_BILLING_SHIPPING_ADDRESS = '722 Harvest Lane, SEDALIA MO US 65301';

    const USER_BILLING_DEFAULT_BILLING = '2413 Capitol Avenue, ROMNEY IN US 47981';
    const USER_BILLING_SHIPPING_DEFAULT_SHIPPING = '1215 Caldwell Road, ROCHESTER NY US 14608';
    const USER_BILLING_SHIPPING_ADDRESS = '722 Harvest Lane, SEDALIA MO US 65301';

    /** @var Role */
    protected $role;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->role = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroUserBundle:Role')
            ->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserAddresses',
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
        ]);
    }

    protected function tearDown()
    {
        unset($this->role);
    }

    /**
     * @dataProvider checkShippingAddressesDataProvider
     * @param array $permissions
     * @param array $capabilities
     * @param array $expected
     */
    public function testCheckShippingAddresses(array $permissions, array $capabilities, array $expected)
    {
        $this->setRolePermissions($permissions['accountEntityPermissions'], $this->getAccountAddressIdentity());
        $this->setRolePermissions($permissions['accountUserEntityPermissions'], $this->getAccountAddressUserIdentity());

        foreach ($capabilities as $capabilityId => $value) {
            $this->setActionPermissions($capabilityId, $value);
        }

        /** @var Order $order */
        $order = $this->getReference('simple_order');

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $order->getId()]));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if (!empty($expected)) {
            // Check shipping addresses
            $this->assertContains('Shipping Address', $crawler->filter('.navbar-static')->html());
            $this->checkAddresses($crawler, self::SHIPPING_ADDRESS, $expected);
        } else {
            $this->assertNotContains('Shipping Address', $crawler->filter('.navbar-static')->html());
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function checkShippingAddressesDataProvider()
    {
        return [
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => true,
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [],
                    'manually' => true
                ],
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [],
                    'manually' => true
                ],
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [],
                    'accountUser' => [],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => false,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => false,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_shipping_account_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_any_backend' => true,
                    'orob2b_order_address_shipping_account_user_use_default_backend' => true,
                    'orob2b_order_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
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
     * @param array $permissions
     * @param array $capabilities
     * @param array $expected
     */
    public function testCheckBillingAddresses(array $permissions, array $capabilities, array $expected)
    {
        $this->setRolePermissions($permissions['accountEntityPermissions'], $this->getAccountAddressIdentity());
        $this->setRolePermissions($permissions['accountUserEntityPermissions'], $this->getAccountAddressUserIdentity());

        foreach ($capabilities as $capabilityId => $value) {
            $this->setActionPermissions($capabilityId, $value);
        }

        /** @var Order $order */
        $order = $this->getReference('simple_order');

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $order->getId()]));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if (!empty($expected)) {
            // Check billing addresses
            $this->assertContains('Billing Address', $crawler->filter('.navbar-static')->html());
            $this->checkAddresses($crawler, self::BILLING_ADDRESS, $expected);
        } else {
            $this->assertNotContains('Billing Address', $crawler->filter('.navbar-static')->html());
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function checkBillingAddressesDataProvider()
    {
        return [
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => true,
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [],
                    'manually' => true
                ],
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [],
                    'manually' => true
                ],
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [],
                    'accountUser' => [],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => false,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => false,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING
                    ],
                    'manually' => true
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => false
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => false
                ]
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'orob2b_order_address_billing_account_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_any_backend' => true,
                    'orob2b_order_address_billing_account_user_use_default_backend' => true,
                    'orob2b_order_address_billing_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_DEFAULT_BILLING,
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::COMPANY_BILLING_SHIPPING_ADDRESS
                    ],
                    'accountUser' => [
                        self::USER_BILLING_DEFAULT_BILLING,
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
                        self::USER_BILLING_SHIPPING_ADDRESS
                    ],
                    'manually' => true
                ]
            ]
        ];
    }

    /**
     * @param Crawler $crawler
     * @param string $addressType
     * @param array $expected
     */
    protected function checkAddresses(Crawler $crawler, $addressType, array $expected)
    {
        if ($expected['manually']) {
            $accountAddressSelector = $crawler
                ->filter('select[name="orob2b_order_type['. $addressType .'][accountAddress]"]')->html();

            $this->assertContains('Enter address manually', $accountAddressSelector);
        }

        // Check account addresses
        if (!empty($expected['account'])) {
            $accountAddresses = $crawler
                ->filter('select[name="orob2b_order_type['. $addressType .'][accountAddress]"]'
                    .' optgroup[label="Account"]')
                ->html();

            foreach ($expected['account'] as $accountAddress) {
                $this->assertContains($accountAddress, $accountAddresses);
            }
        }

        // Check account users addresses
        if (!empty($expected['accountUser'])) {
            $accountUserAddresses = $crawler
                ->filter('select[name="orob2b_order_type['. $addressType .'][accountAddress]"]'
                    .' optgroup[label="Account User"]')
                ->html();

            foreach ($expected['accountUser'] as $accountUserAddress) {
                $this->assertContains($accountUserAddress, $accountUserAddresses);
            }
        }
    }

    /**
     * @param int $level
     * @param AclPrivilegeIdentity $identity
     */
    protected function setRolePermissions($level, AclPrivilegeIdentity $identity)
    {
        $aclPrivilege = new AclPrivilege();

        $aclPrivilege->setIdentity($identity);
        $permissions = [
            new AclPermission('VIEW', $level)
        ];

        foreach ($permissions as $permission) {
            $aclPrivilege->addPermission($permission);
        }

        $this->getContainer()->get('oro_security.acl.privilege_repository')->savePrivileges(
            $this->getContainer()->get('oro_security.acl.manager')->getSid($this->role),
            new ArrayCollection([$aclPrivilege])
        );
    }

    /**
     * @return AclPrivilegeIdentity
     */
    protected function getAccountAddressIdentity()
    {
        return new AclPrivilegeIdentity(
            'entity:OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
            'orob2b.account.accountaddress.entity_label'
        );
    }

    /**
     * @return AclPrivilegeIdentity
     */
    protected function getAccountAddressUserIdentity()
    {
        return new AclPrivilegeIdentity(
            'entity:OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
            'orob2b.account.accountuseraddress.entity_label'
        );
    }

    /**
     * @param string $actionId
     * @param bool $value
     */
    protected function setActionPermissions($actionId, $value)
    {
        $aclManager = $this->getContainer()->get('oro_security.acl.manager');

        $sid = $aclManager->getSid($this->role);
        $oid = $aclManager->getOid('action:' . $actionId);
        $builder = $aclManager->getMaskBuilder($oid);
        $mask = $value ? $builder->reset()->add('EXECUTE')->get() : $builder->reset()->get();
        $aclManager->setPermission($sid, $oid, $mask, true);

        $aclManager->flush();
    }
}
