<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddress;
use Oro\Bundle\OrderBundle\Tests\Unit\Provider\AbstractQuoteAddressProviderTest;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;

class QuoteAddressProviderTest extends AbstractQuoteAddressProviderTest
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclHelper */
    protected $aclHelper;

    /** @var QuoteAddressProvider */
    protected $provider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new QuoteAddressProvider(
            $this->securityFacade,
            $this->registry,
            $this->aclHelper,
            $this->accountAddressClass,
            $this->accountUserAddressClass
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown type "test", known types are: shipping
     */
    public function testGetAccountAddressesUnsupportedType()
    {
        $this->provider->getAccountAddresses(new Account(), 'test');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown type "test", known types are: shipping
     */
    public function testGetAccountUserAddressesUnsupportedType()
    {
        $this->provider->getAccountUserAddresses(new AccountUser(), 'test');
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function accountUserAddressPermissions()
    {
        return [
            [
                'shipping',
                [
                    'oro_quote_address_shipping_account_user_use_any' => false,
                    'oro_quote_address_shipping_account_user_use_default' => false,
                ],
                null,
                [],
                new AccountUser()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_account_user_use_any' => true
                ],
                'getAddressesByType',
                [new AccountUserAddress()],
                new AccountUser()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_account_user_use_any' => false,
                    'oro_quote_address_shipping_account_user_use_default' => true
                ],
                'getDefaultAddressesByType',
                [new AccountUserAddress()],
                new AccountUser()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => false,
                ],
                null,
                [],
                new \stdClass()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_account_user_use_any_backend' => true
                ],
                'getAddressesByType',
                [new AccountUserAddress()],
                new \stdClass()
            ],
            [
                'shipping',
                [
                    'oro_quote_address_shipping_account_user_use_any_backend' => false,
                    'oro_quote_address_shipping_account_user_use_default_backend' => true
                ],
                'getDefaultAddressesByType',
                [new AccountUserAddress()],
                new \stdClass()
            ]
        ];
    }

    /**
     * @return array
     */
    public function accountAddressPermissions()
    {
        return [
            ['shipping', 'oro_quote_address_shipping_account_use_any', new AccountUser()],
            ['shipping', 'oro_quote_address_shipping_account_use_any_backend', new \stdClass()],
        ];
    }
}
