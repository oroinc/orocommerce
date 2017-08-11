<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Model\RequestManager;
use Oro\Component\Testing\Unit\EntityTrait;

class RequestManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RequestManager
     */
    protected $requestManager;

    /**
     * @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenAccessor;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var GuestCustomerUserManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $guestCustomerUserManager;

    public function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->guestCustomerUserManager = $this->createMock(GuestCustomerUserManager::class);

        $this->requestManager = new RequestManager(
            $this->tokenAccessor,
            $this->doctrineHelper,
            $this->guestCustomerUserManager
        );
    }

    /**
     * @dataProvider loggedUserDataProvider
     *
     * @param mixed $user
     * @param Request $request
     * @param Request $expected
     */
    public function testAppendUserData($user, Request $request, Request $expected)
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->requestManager->appendUserData($request);

        $this->assertEquals($expected->getEmail(), $request->getEmail());
        $this->assertEquals($expected->getFirstName(), $request->getFirstName());
        $this->assertEquals($expected->getLastName(), $request->getLastName());
        $this->assertEquals($expected->getCompany(), $request->getCompany());
    }

    /**
     * @return array
     */
    public function loggedUserDataProvider()
    {
        $customer = new Customer();
        $customer->setName('customer name');

        $user = new CustomerUser();
        $user->setCustomer($customer);
        $user->setEmail('test@example.com');
        $user->setFirstName('first name');
        $user->setLastName('last name');

        $request = new Request();
        $request->setCustomerUser($user);
        $request->setCustomer($user->getCustomer());

        return [
            'empty user' => [
                'user' => null,
                'request' => new Request(),
                'expected' => new Request(),
            ],
            'without additional data' => [
                'user' => $user,
                'request' => $request,
                'expected' => $request->setEmail($user->getEmail())
                    ->setFirstName($user->getFirstName())
                    ->setLastName($user->getLastName())
                    ->setCompany($user->getCustomer()->getName()),
            ],
            'with email' => [
                'user' => $user,
                'request' => $request->setEmail('test1@example.com'),
                'expected' => $request->setFirstName($user->getFirstName())
                    ->setLastName($user->getLastName())
                    ->setCompany($user->getCustomer()->getName()),
            ],
            'with first name' => [
                'user' => $user,
                'request' => $request->setFirstName('first name 1'),
                'expected' => $request->setEmail($user->getEmail())
                    ->setLastName($user->getLastName())
                    ->setCompany($user->getCustomer()->getName()),
            ],
            'with last name' => [
                'user' => $user,
                'request' => $request->setLastName('last name 1'),
                'expected' => $request->setEmail($user->getEmail())
                    ->setFirstName($user->getFirstName())
                    ->setCompany($user->getCustomer()->getName()),
            ],
            'with company' => [
                'user' => $user,
                'request' => $request->setCompany('company'),
                'expected' => $request->setEmail($user->getEmail())
                    ->setFirstName($user->getFirstName())
                    ->setLastName($user->getLastName()),
            ],
        ];
    }

    public function testAppendUserDataWithGuestCustomerUser()
    {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $customerUser->setEmail('test@example.com');
        $customerUser->setOrganization(new Organization());

        $visitor = new CustomerVisitor();
        $visitor->setCustomerUser($customerUser);

        /** @var AnonymousCustomerUserToken|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($visitor);

        $this->tokenAccessor
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $request = new Request();

        $this->requestManager->appendUserData($request);

        $this->assertEquals('test@example.com', $request->getEmail());
    }

    public function testAppendUserDataCreateGuestCustomerUser()
    {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $customerUser->setOrganization(new Organization());

        $visitor = new CustomerVisitor();

        $this->guestCustomerUserManager
            ->expects($this->once())
            ->method('generateGuestCustomerUser')
            ->with([
                'email' => 'test@example.com',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
            ])
            ->willReturn($customerUser);

        /** @var EntityManager||\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($customerUser);

        $em->expects($this->once())
            ->method('flush')
            ->with($customerUser);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(CustomerUser::class)
            ->willReturn($em);

        /** @var AnonymousCustomerUserToken|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($visitor);

        $this->tokenAccessor
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $request = new Request();
        $request->setEmail('test@example.com');
        $request->setFirstName('first_name');
        $request->setLastName('last_name');

        $this->requestManager->appendUserData($request);
    }

    public function testAddProductItemToRequest()
    {
        $productId = 1;
        $unitCode = 'bottle';
        $quantity = 10;
        $data = [
            $productId => [[
                'unit' => $unitCode,
                'quantity' => $quantity,
            ]],
        ];
        $request = new Request();
        
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        $unit = $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => $unitCode]);

        $productReposiotry = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->at(0))
            ->method('getEntityRepositoryForClass')
            ->willReturn($productReposiotry);
        $productReposiotry->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$productId]])
            ->willReturn([$product]);

        $unitRepository = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->at(1))
            ->method('getEntityRepositoryForClass')
            ->willReturn($unitRepository);
        $unitRepository->expects($this->once())
            ->method('getProductsUnitsByCodes')
            ->with([$productId], [$unitCode])
            ->willReturn([$unitCode => $unit]);
        $this->assertEmpty($request->getRequestProducts());

        $this->requestManager->addProductLineItemsToRequest($request, $data);

        $this->assertNotEmpty($request->getRequestProducts());
    }
}
