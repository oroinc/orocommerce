<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\RFPListener;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class RFPListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DefaultUserProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenAccessor;

    /** @var RFPListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->listener = new RFPListener($this->defaultUserProvider, $this->tokenAccessor);
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
                'token' => new AnonymousCustomerUserToken(''),
                'checkout' => (new Request())->setOwner(new User())
            ]
        ];
    }

    /**
     * @dataProvider persistSetDefaultOwnerDataProvider
     *
     * @param string $token
     * @param Request $request
     */
    public function testPrePersistSetDefaultOwner($token, Request $request)
    {
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

    /**
     * @return array
     */
    public function persistSetDefaultOwnerDataProvider()
    {
        return [
            'with token and without owner' => [
                'token' => new AnonymousCustomerUserToken(''),
                'checkout' => new Request()
            ]
        ];
    }
}
