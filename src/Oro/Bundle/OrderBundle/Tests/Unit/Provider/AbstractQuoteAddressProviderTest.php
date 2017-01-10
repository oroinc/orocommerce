<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

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
    protected $customerAddressClass = 'class1';

    /** @var string */
    protected $customerUserAddressClass = 'class2';

    /**
     * @var AddressProviderInterface
     */
    protected $provider;

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
     * @dataProvider customerAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetCustomerAddressesNotGranted($type, $expectedPermission, $loggedUser)
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
                        ['VIEW;entity:' . $this->customerAddressClass, null, false],
                    ]
                )
            );

        $repository = $this->assertCustomerAddressRepositoryCall();
        $repository->expects($this->never())
            ->method($this->anything());

        $this->provider->getCustomerAddresses(new Customer(), $type);

        // cache
        $this->provider->getCustomerAddresses(new Customer(), $type);
    }

    /**
     * @dataProvider customerAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetCustomerAddressesGrantedAny($type, $expectedPermission, $loggedUser)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $customer = new Customer();
        $addresses = [new CustomerAddress()];

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($expectedPermission)
            ->willReturn(true);

        $repository = $this->assertCustomerAddressRepositoryCall();
        $repository->expects($this->once())
            ->method('getAddressesByType')
            ->with($customer, $type, $this->aclHelper)
            ->will($this->returnValue($addresses));

        $this->assertEquals($addresses, $this->provider->getCustomerAddresses($customer, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getCustomerAddresses($customer, $type));
    }

    /**
     * @dataProvider customerAddressPermissions
     * @param string $type
     * @param string $expectedPermission
     * @param object $loggedUser
     */
    public function testGetCustomerAddressesGrantedView($type, $expectedPermission, $loggedUser)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $customer = new Customer();
        $addresses = [new CustomerAddress()];

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        [$expectedPermission, null, false],
                        ['VIEW;entity:' . $this->customerAddressClass, null, true],
                    ]
                )
            );

        $repository = $this->assertCustomerAddressRepositoryCall();
        $repository->expects($this->never())
            ->method('getAddressesByType');

        $repository->expects($this->once())
            ->method('getDefaultAddressesByType')
            ->with($customer, $type, $this->aclHelper)
            ->will($this->returnValue($addresses));

        $this->assertEquals($addresses, $this->provider->getCustomerAddresses($customer, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getCustomerAddresses($customer, $type));
    }

    /**
     * @dataProvider customerUserAddressPermissions
     * @param string $type
     * @param array $expectedCalledPermissions
     * @param string $calledRepositoryMethod
     * @param array $addresses
     * @param object $loggedUser
     */
    public function testGetCustomerUserAddresses(
        $type,
        array $expectedCalledPermissions,
        $calledRepositoryMethod,
        array $addresses,
        $loggedUser
    ) {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($loggedUser));

        $customerUser = new CustomerUser();

        $permissionsValueMap = [];
        foreach ($expectedCalledPermissions as $permission => $decision) {
            $permissionsValueMap[] = [$permission, null, $decision];
        }

        $this->securityFacade->expects($this->exactly(count($expectedCalledPermissions)))
            ->method('isGranted')
            ->will($this->returnValueMap($permissionsValueMap));

        $repository = $this->assertCustomerUserAddressRepositoryCall();
        if ($calledRepositoryMethod) {
            $repository->expects($this->once())
                ->method($calledRepositoryMethod)
                ->with($customerUser, $type, $this->aclHelper)
                ->will($this->returnValue($addresses));
        } else {
            $repository->expects($this->never())
                ->method($this->anything());
        }

        $this->assertEquals($addresses, $this->provider->getCustomerUserAddresses($customerUser, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getCustomerUserAddresses($customerUser, $type));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertCustomerAddressRepositoryCall()
    {
        $repository = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Repository\CustomerAddressRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->createMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->customerAddressClass)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->customerAddressClass)
            ->will($this->returnValue($manager));

        return $repository;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertCustomerUserAddressRepositoryCall()
    {
        $repository = $this
            ->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserAddressRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->createMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->customerUserAddressClass)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->customerUserAddressClass)
            ->will($this->returnValue($manager));

        return $repository;
    }
}
