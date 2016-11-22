<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string
     */
    protected $paymentTermClass = 'testClass';

    /**
     * @var PaymentTermProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->tokenStorage = $this->getMock(TokenStorageInterface::class);

        $this->provider = new PaymentTermProvider(
            $this->registry,
            $this->tokenStorage,
            $this->eventDispatcher,
            $this->paymentTermClass
        );
    }

    protected function tearDown()
    {
        unset($this->registry, $this->paymentTermClass, $this->provider);
    }

    /**
     * @dataProvider getPaymentTermDataProvider
     *
     * @param Account $account
     * @param array $repositoryMethods
     * @param PaymentTerm|null $expected
     */
    public function testGetPaymentTerm(Account $account, array $repositoryMethods, PaymentTerm $expected = null)
    {
        $repository = $this->assertPaymentTermRepositoryCall();

        foreach ($repositoryMethods as $methodName => $methodParams) {
            $invocationMocker = $repository->expects($methodParams['expects'])
                ->method($methodName);

            if (array_key_exists('willReturn', $methodParams)) {
                $invocationMocker->with($methodParams['with'])
                    ->willReturn($methodParams['willReturn']);
            }
        }

        $this->assertEquals($expected, $this->provider->getPaymentTerm($account));
    }

    /**
     * @return array
     */
    public function getPaymentTermDataProvider()
    {
        $account = new Account();

        $accountWithGroup = new Account();
        $accountWithGroup->setGroup(new AccountGroup());

        $paymentTerm = new PaymentTerm();

        return [
            [
                'account' => $account,
                'repositoryMethods' => [
                    'getOnePaymentTermByAccount' => [
                        'expects' => $this->once(),
                        'with' => $account,
                        'willReturn' => $paymentTerm,
                    ],
                    'getOnePaymentTermByAccountGroup' => [
                        'expects' => $this->never(),
                    ],
                ],
                'expected' => $paymentTerm,
            ],
            [
                'account' => $account,
                'repositoryMethods' => [
                    'getOnePaymentTermByAccount' => [
                        'expects' => $this->once(),
                        'with' => $account,
                        'willReturn' => null,
                    ],
                    'getOnePaymentTermByAccountGroup' => [
                        'expects' => $this->never(),
                    ],
                ],
                'expected' => null,
            ],
            [
                'account' => $accountWithGroup,
                'repositoryMethods' => [
                    'getOnePaymentTermByAccount' => [
                        'expects' => $this->once(),
                        'with' => $accountWithGroup,
                        'willReturn' => null,
                    ],
                    'getOnePaymentTermByAccountGroup' => [
                        'expects' => $this->once(),
                        'with' => $accountWithGroup->getGroup(),
                        'willReturn' => $paymentTerm,
                    ],
                ],
                'expected' => $paymentTerm,
            ],
            [
                'account' => $accountWithGroup,
                'repositoryMethods' => [
                    'getOnePaymentTermByAccount' => [
                        'expects' => $this->once(),
                        'with' => $accountWithGroup,
                        'willReturn' => null,
                    ],
                    'getOnePaymentTermByAccountGroup' => [
                        'expects' => $this->once(),
                        'with' => $accountWithGroup->getGroup(),
                        'willReturn' => null,
                    ],
                ],
                'expected' => null,
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertPaymentTermRepositoryCall()
    {
        $repository = $this->getMockBuilder('Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($this->paymentTermClass)
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->paymentTermClass)
            ->willReturn($manager);

        return $repository;
    }

    public function testGetCurrentFormResolvePaymentTermEvent()
    {
        $paymentTerm = new PaymentTerm();
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class))
            ->will($this->returnCallback(function ($eventName, ResolvePaymentTermEvent  $event) use ($paymentTerm) {
                $event->setPaymentTerm($paymentTerm);
            }));

        $this->assertSame($paymentTerm, $this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentWithoutToken()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class));
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn(null);

        $this->assertNull($this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentWithoutUser()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class));
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn(null);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertNull($this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrent()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class));
        $repository = $this->assertPaymentTermRepositoryCall();
        $paymentTerm = new PaymentTerm();
        $repository->expects($this->once())->method('getOnePaymentTermByAccount')->willReturn($paymentTerm);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $accountUser = new AccountUser();
        $accountUser->setAccount(new Account());
        $token->expects($this->once())->method('getUser')->willReturn($accountUser);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertSame($paymentTerm, $this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentNoAccountGroup()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class));
        $repository = $this->assertPaymentTermRepositoryCall();
        $repository->expects($this->once())->method('getOnePaymentTermByAccount')->willReturn(null);
        $repository->expects($this->never())->method('getOnePaymentTermByAccountGroup');

        $token = $this->getMock(TokenInterface::class);
        $accountUser = new AccountUser();
        $accountUser->setAccount(new Account());
        $token->expects($this->once())->method('getUser')->willReturn($accountUser);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertSame(null, $this->provider->getCurrentPaymentTerm());
    }

    public function testGetCurrentFromAccountGroup()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(ResolvePaymentTermEvent::NAME), $this->isInstanceOf(ResolvePaymentTermEvent::class));
        $repository = $this->assertPaymentTermRepositoryCall();
        $paymentTerm = new PaymentTerm();
        $repository->expects($this->once())->method('getOnePaymentTermByAccount')->willReturn(null);
        $repository->expects($this->once())->method('getOnePaymentTermByAccountGroup')->willReturn($paymentTerm);

        $token = $this->getMock(TokenInterface::class);
        $accountUser = new AccountUser();
        $account = new Account();
        $account->setGroup(new AccountGroup());
        $accountUser->setAccount($account);
        $token->expects($this->once())->method('getUser')->willReturn($accountUser);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertSame($paymentTerm, $this->provider->getCurrentPaymentTerm());
    }
}
