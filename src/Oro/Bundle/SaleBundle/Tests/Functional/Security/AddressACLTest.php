<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Security;

use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\OrderBundle\Tests\Functional\Security\AbstractAddressACLTest;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class AddressACLTest extends AbstractAddressACLTest
{
    const SHIPPING_ADDRESS = 'shippingAddress';

    const COMPANY_BILLING_DEFAULT_BILLING = '2413 Capitol Avenue, ROMNEY IN US 47981';
    const COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING = '1215 Caldwell Road, ROCHESTER NY US 14608';
    const COMPANY_BILLING_SHIPPING_ADDRESS = '722 Harvest Lane, SEDALIA MO US 65301';

    const USER_BILLING_DEFAULT_BILLING = '2413 Capitol Avenue, ROMNEY IN US 47981';
    const USER_BILLING_SHIPPING_DEFAULT_SHIPPING = '1215 Caldwell Road, ROCHESTER NY US 14608';
    const USER_BILLING_SHIPPING_ADDRESS = '722 Harvest Lane, SEDALIA MO US 65301';

    /** @var Role */
    protected $role;

    /** @var string */
    protected $formName = 'oro_sale_quote';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->role = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroUserBundle:Role')
            ->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        $this->loadFixtures([
            LoadQuoteData::class
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

        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.3');

        $crawler = $this->client->request('GET', $this->getUrl('oro_sale_quote_update', ['id' => $quote->getId()]));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);


        if (!empty($expected)) {
            // Check shipping addresses
            $this->assertContains('Shipping Address', $crawler->filter('.navbar-static')->html());
            $this->checkAddresses($crawler, $this->formName, self::SHIPPING_ADDRESS, $expected);
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => true
                ],
                'expected' => [
                    'account' => [
                        self::COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING,
                    ],
                    'accountUser' => [
                        self::USER_BILLING_SHIPPING_DEFAULT_SHIPPING,
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::NONE_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::NONE_LEVEL,
                ],
                'capabilities' => [
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => false,
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => false
                ],
                'expected' => []
            ],
            [
                'permissions' => [
                    'accountEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                    'accountUserEntityPermissions' => AccessLevel::SYSTEM_LEVEL,
                ],
                'capabilities' => [
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => false
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
                    'oro_quote_address_shipping_account_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_any_backend' => true,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true,
                    'oro_quote_address_shipping_allow_manual_backend' => true
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
}
