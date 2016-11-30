<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

abstract class AbstractQuoteAddressProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclHelper */
    protected $aclHelper;

    /** @var string */
    protected $accountAddressClass = 'class1';

    /** @var string */
    protected $accountUserAddressClass = 'class2';

    /**
     * @var AddressProviderInterface
     */
    protected $provider;

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
     * @dataProvider accountAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetAccountAddressesNotGranted($type, $expectedPermission, $loggedUser)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        [$expectedPermission, null, false],
                        ['VIEW;entity:' . $this->accountAddressClass, null, false],
                    ]
                )
            );

        $repository = $this->assertAccountAddressRepositoryCall();
        $repository->expects($this->never())
            ->method($this->anything());

        $this->provider->getAccountAddresses(new Account(), $type);

        // cache
        $this->provider->getAccountAddresses(new Account(), $type);
    }

    /**
     * @dataProvider accountAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetAccountAddressesGrantedAny($type, $expectedPermission, $loggedUser)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $account = new Account();
        $addresses = [new AccountAddress()];

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($expectedPermission)
            ->willReturn(true);

        $repository = $this->assertAccountAddressRepositoryCall();
        $repository->expects($this->once())
            ->method('getAddressesByType')
            ->with($account, $type, $this->aclHelper)
            ->will($this->returnValue($addresses));

        $this->assertEquals($addresses, $this->provider->getAccountAddresses($account, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getAccountAddresses($account, $type));
    }

    /**
     * @dataProvider accountAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetAccountAddressesGrantedView($type, $expectedPermission, $loggedUser)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $account = new Account();
        $addresses = [new AccountAddress()];

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        [$expectedPermission, null, false],
                        ['VIEW;entity:' . $this->accountAddressClass, null, true],
                    ]
                )
            );

        $repository = $this->assertAccountAddressRepositoryCall();
        $repository->expects($this->never())
            ->method('getAddressesByType');

        $repository->expects($this->once())
            ->method('getDefaultAddressesByType')
            ->with($account, $type, $this->aclHelper)
            ->will($this->returnValue($addresses));

        $this->assertEquals($addresses, $this->provider->getAccountAddresses($account, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getAccountAddresses($account, $type));
    }

    /**
     * @dataProvider accountUserAddressPermissions
     * @param string $type
     * @param array $expectedCalledPermissions
     * @param string $calledRepositoryMethod
     * @param array $addresses
     * @param object $loggedUser
     */
    public function testGetAccountUserAddresses(
        $type,
        array $expectedCalledPermissions,
        $calledRepositoryMethod,
        array $addresses,
        $loggedUser
    ) {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $accountUser = new AccountUser();

        $permissionsValueMap = [];
        foreach ($expectedCalledPermissions as $permission => $decision) {
            $permissionsValueMap[] = [$permission, null, $decision];
        }

        $this->securityFacade->expects($this->exactly(count($expectedCalledPermissions)))
            ->method('isGranted')
            ->will($this->returnValueMap($permissionsValueMap));

        $repository = $this->assertAccountUserAddressRepositoryCall();
        if ($calledRepositoryMethod) {
            $repository->expects($this->once())
                ->method($calledRepositoryMethod)
                ->with($accountUser, $type, $this->aclHelper)
                ->will($this->returnValue($addresses));
        } else {
            $repository->expects($this->never())
                ->method($this->anything());
        }

        $this->assertEquals($addresses, $this->provider->getAccountUserAddresses($accountUser, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getAccountUserAddresses($accountUser, $type));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertAccountAddressRepositoryCall()
    {
        $repository = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Repository\AccountAddressRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->accountAddressClass)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->accountAddressClass)
            ->will($this->returnValue($manager));

        return $repository;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertAccountUserAddressRepositoryCall()
    {
        $repository = $this
            ->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Repository\AccountUserAddressRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->accountUserAddressClass)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->accountUserAddressClass)
            ->will($this->returnValue($manager));

        return $repository;
    }
}
