<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Provider;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;

class OrderAddressSecurityProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderAddressSecurityProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderAddressProvider */
    protected $orderAddressProvider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressProvider = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OrderAddressSecurityProvider(
            $this->securityFacade,
            $this->orderAddressProvider,
            'AccountOrderClass',
            'AccountUserOrderClass'
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->provider, $this->orderAddressProvider);
    }

    /**
     * @dataProvider manualEditDataProvider
     * @param string $type
     * @param string $permissionName
     * @param bool $permission
     */
    public function testIsManualEditGranted($type, $permissionName, $permission)
    {
        $this->securityFacade->expects($this->atLeastOnce())->method('isGranted')->with($permissionName)
            ->willReturn($permission);

        $this->assertEquals($permission, $this->provider->isManualEditGranted($type));
    }

    /**
     * @return array
     */
    public function manualEditDataProvider()
    {
        return [
            ['billing', 'orob2b_order_address_billing_allow_manual_backend', true],
            ['billing', 'orob2b_order_address_billing_allow_manual_backend', false],
            ['shipping', 'orob2b_order_address_shipping_allow_manual_backend', true],
            ['shipping', 'orob2b_order_address_shipping_allow_manual_backend', false],
        ];
    }

    /**
     * @dataProvider permissionsDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string $userClass
     * @param string $addressType
     * @param array|null $isGranted
     * @param bool $hasAccountAddresses
     * @param bool $hasAccountUserAddresses
     * @param bool $hasEntity
     * @param bool $isAddressGranted
     * @param bool $isAccountAddressGranted
     * @param bool $isAccountUserAddressGranted
     */
    public function testIsAddressGranted(
        $userClass,
        $addressType,
        $isGranted,
        $hasAccountAddresses,
        $hasAccountUserAddresses,
        $hasEntity,
        $isAddressGranted,
        $isAccountAddressGranted,
        $isAccountUserAddressGranted
    ) {
        $this->orderAddressProvider->expects($this->any())->method('getAccountAddresses')
            ->willReturn($hasAccountAddresses);
        $this->orderAddressProvider->expects($this->any())->method('getAccountUserAddresses')
            ->willReturn($hasAccountUserAddresses);

        $this->securityFacade->expects($this->any())->method('getLoggedUser')->willReturn(new $userClass);
        $this->securityFacade->expects($this->any())->method('isGranted')->with($this->isType('string'))
            ->will($this->returnValueMap((array)$isGranted));

        $order = null;
        $account = null;
        $accountUser = null;
        if ($hasEntity) {
            $account = new Account();
            $accountUser = new AccountUser();
        }
        $order = (new Order())->setAccount($account)->setAccountUser($accountUser);

        $this->assertEquals(
            $isAddressGranted,
            $this->provider->isAddressGranted($order, $addressType)
        );
        $this->assertEquals(
            $isAccountAddressGranted,
            $this->provider->isAccountAddressGranted($addressType, $account)
        );
        $this->assertEquals(
            $isAccountUserAddressGranted,
            $this->provider->isAccountUserAddressGranted($addressType, $accountUser)
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function permissionsDataProvider()
    {
        $finder = new Finder();
        $yaml = new Parser();
        $data = [];

        $finder->files()->in(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures');
        foreach ($finder as $file) {
            $data = $data + $yaml->parse(file_get_contents($file));
        }

        return $data;
    }
}
