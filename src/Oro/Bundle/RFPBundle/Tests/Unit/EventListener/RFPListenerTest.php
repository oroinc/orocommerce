<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\RFPListener;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class RFPListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefaultUserProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var GuestCustomerUserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUserManager;

    /** @var RFPListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->customerUserManager = $this->createMock(GuestCustomerUserManager::class);

        $this->listener = new RFPListener($this->defaultUserProvider, $this->tokenAccessor, $this->customerUserManager);
    }

    /**
     * @dataProvider persistNotSetDefaultOwnerDataProvider
     *
     * @param string $token
     * @param Request $request
     */
    public function testPrePersistNotSetDefaultOwner($token, Request $request)
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->listener->prePersist($request);
        $this->assertNotSame($newUser, $request->getOwner());
    }

    /**
     * @return array
     */
    public function persistNotSetDefaultOwnerDataProvider()
    {
        return [
            'without token and without owner' => [
                'token' => null,
                'checkout' => new Request()
            ],
            'unsupported token and without owner' => [
                'token' => new \stdClass(),
                'checkout' => new Request()
            ],
            'with owner' => [
                'token' => $this->createAnonymousToken(),
                'checkout' => (new Request())->setOwner(new User())
            ]
        ];
    }

    public function testPrePersistSetDefaultOwner()
    {
        $token = $this->createAnonymousToken();
        $request = new Request();

        $this->tokenAccessor
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->defaultUserProvider
            ->expects($this->once())
            ->method('getDefaultUser')
            ->with('oro_rfp', 'default_guest_rfp_owner')
            ->willReturn($newUser);

        $this->listener->prePersist($request);
        $this->assertSame($newUser, $request->getOwner());
    }

    public function testPrePersistCreatesNewCustomerUser()
    {
        $token = $this->createAnonymousToken();
        $request = new Request();
        $request->setEmail('Some mail')
            ->setFirstName('Firstname')
            ->setLastName('Lastname');

        $this->tokenAccessor
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->defaultUserProvider
            ->expects($this->once())
            ->method('getDefaultUser')
            ->with('oro_rfp', 'default_guest_rfp_owner')
            ->willReturn($newUser);

        $customerUser = new CustomerUser();
        $this->customerUserManager->expects($this->once())
            ->method('generateGuestCustomerUser')
            ->with(
                [
                    'email' => $request->getEmail(),
                    'first_name' => $request->getFirstName(),
                    'last_name' => $request->getLastName()
                ]
            )
            ->willReturn($customerUser);
        $this->listener->prePersist($request);
        $this->assertSame($newUser, $request->getOwner());
        $this->assertSame($customerUser, $request->getCustomerUser());
    }

    /**
     * @return AnonymousCustomerUserToken
     */
    protected function createAnonymousToken()
    {
        $visitor = new CustomerVisitor();
        $visitor->setCustomerUser(new CustomerUser);

        return new AnonymousCustomerUserToken('', [], $visitor);
    }
}
