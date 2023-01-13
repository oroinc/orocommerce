<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerAddressRepository;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserAddressRepository;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class AbstractQuoteAddressProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclHelper */
    protected $aclHelper;

    /** @var string */
    protected $customerAddressClass = 'class1';

    /** @var string */
    protected $customerUserAddressClass = 'class2';

    /** @var AddressProviderInterface */
    protected $provider;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
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
     * @dataProvider customerAddressPermissions
     */
    public function testGetCustomerAddressesNotGranted(string $type, string $expectedPermission, object $loggedUser)
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($loggedUser);

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                [$expectedPermission, null, false],
                ['VIEW', 'entity:' . $this->customerAddressClass, false],
            ]);

        $repository = $this->assertCustomerAddressRepositoryCall();
        $repository->expects($this->never())
            ->method($this->anything());

        $this->provider->getCustomerAddresses(new Customer(), $type);

        // cache
        $this->provider->getCustomerAddresses(new Customer(), $type);
    }

    /**
     * @dataProvider customerAddressPermissions
     */
    public function testGetCustomerAddressesGrantedAny(string $type, string $expectedPermission, object $loggedUser)
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($loggedUser);

        $customer = new Customer();
        $addresses = [new CustomerAddress()];

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($expectedPermission)
            ->willReturn(true);

        $repository = $this->assertCustomerAddressRepositoryCall();
        $repository->expects($this->once())
            ->method('getAddressesByType')
            ->with($customer, $type, $this->aclHelper)
            ->willReturn($addresses);

        $this->assertEquals($addresses, $this->provider->getCustomerAddresses($customer, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getCustomerAddresses($customer, $type));
    }

    /**
     * @dataProvider customerAddressPermissions
     */
    public function testGetCustomerAddressesGrantedView(string $type, string $expectedPermission, object $loggedUser)
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($loggedUser);

        $customer = new Customer();
        $addresses = [new CustomerAddress()];

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                [$expectedPermission, null, false],
                ['VIEW', 'entity:' . $this->customerAddressClass, true],
            ]);

        $repository = $this->assertCustomerAddressRepositoryCall();
        $repository->expects($this->never())
            ->method('getAddressesByType');

        $repository->expects($this->once())
            ->method('getDefaultAddressesByType')
            ->with($customer, $type, $this->aclHelper)
            ->willReturn($addresses);

        $this->assertEquals($addresses, $this->provider->getCustomerAddresses($customer, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getCustomerAddresses($customer, $type));
    }

    /**
     * @dataProvider customerUserAddressPermissions
     */
    public function testGetCustomerUserAddresses(
        string $type,
        array $expectedCalledPermissions,
        ?string $calledRepositoryMethod,
        array $addresses,
        object $loggedUser
    ) {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($loggedUser);

        $customerUser = new CustomerUser();

        $permissionsValueMap = [];
        foreach ($expectedCalledPermissions as $permission => $decision) {
            $permissionsValueMap[] = [$permission, null, $decision];
        }

        $this->authorizationChecker->expects($this->exactly(count($expectedCalledPermissions)))
            ->method('isGranted')
            ->willReturnMap($permissionsValueMap);

        $repository = $this->assertCustomerUserAddressRepositoryCall();
        if ($calledRepositoryMethod) {
            $repository->expects($this->once())
                ->method($calledRepositoryMethod)
                ->with($customerUser, $type, $this->aclHelper)
                ->willReturn($addresses);
        } else {
            $repository->expects($this->never())
                ->method($this->anything());
        }

        $this->assertEquals($addresses, $this->provider->getCustomerUserAddresses($customerUser, $type));

        // cache
        $this->assertEquals($addresses, $this->provider->getCustomerUserAddresses($customerUser, $type));
    }

    /**
     * @return CustomerAddressRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function assertCustomerAddressRepositoryCall()
    {
        $repository = $this->createMock(CustomerAddressRepository::class);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->customerAddressClass)
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->customerAddressClass)
            ->willReturn($manager);

        return $repository;
    }

    /**
     * @return CustomerUserAddressRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function assertCustomerUserAddressRepositoryCall()
    {
        $repository = $this->createMock(CustomerUserAddressRepository::class);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->customerUserAddressClass)
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->customerUserAddressClass)
            ->willReturn($manager);

        return $repository;
    }
}
