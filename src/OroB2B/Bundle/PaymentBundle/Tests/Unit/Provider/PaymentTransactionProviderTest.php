<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PaymentTransactionProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var string */
    protected $paymentTransactionClass = 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction';

    /** @var PaymentTransactionProvider */
    protected $provider;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var TokenStorage|\PHPUnit_Framework_MockObject_MockObject */
    protected $tokenStorage;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with($this->paymentTransactionClass)
            ->willReturn($this->repository);

        $this->tokenStorage =
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->provider = new PaymentTransactionProvider(
            $this->doctrineHelper,
            $this->tokenStorage,
            $this->paymentTransactionClass
        );
    }

    protected function tearDown()
    {
        unset($this->provider, $this->doctrineHelper, $this->repository, $this->tokenStorage);
    }

    /**
     * @dataProvider getPaymentTransactionsDataProvider
     *
     * @param array $data
     * @param array $expected
     */
    public function testGetPaymentTransactions(array $data, array $expected)
    {
        $this->configureDoctrineHelper($data);

        $result = [new PaymentTransaction(), new PaymentTransaction()];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with($expected)
            ->willReturn($result);

        $this->prepareAccountUser($expected['frontendOwner'], $data['frontendOwnerToken']);

        $actual = $this->provider->getPaymentTransactions($data['entity'], $data['filter']);
        $this->assertSame($result, $actual);
    }

    /**
     * @return array
     */
    public function getPaymentTransactionsDataProvider()
    {
        $entityId = 10;
        $entityClass = 'TestClass';

        return [
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'filter' => [
                        'testOption' => 'testOption',
                    ],
                    'frontendOwnerToken' => $this->getMock(
                        'Symfony\Component\Security\Core\Authentication\Token\TokenInterface'
                    )
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => new AccountUser(),
                ],
            ],
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'filter' => [
                        'testOption' => 'testOption',
                    ],
                    'frontendOwnerToken' => $this->getMock(
                        'Symfony\Component\Security\Core\Authentication\Token\TokenInterface'
                    )
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => null,
                ],
            ],
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'filter' => [
                        'testOption' => 'testOption',
                    ],
                    'frontendOwnerToken' => null
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getPaymentTransactionDataProvider
     *
     * @param array $data
     * @param array $expected
     */
    public function testGetPaymentTransaction(array $data, array $expected)
    {
        $this->configureDoctrineHelper($data);

        $result = new PaymentTransaction();
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with($expected)
            ->willReturn($result);

        $this->prepareAccountUser($expected['frontendOwner'], $data['frontendOwnerToken']);

        $actual = $this->provider->getPaymentTransaction($data['entity'], $data['filter']);
        $this->assertSame($result, $actual);
    }

    /**
     * @return array
     */
    public function getPaymentTransactionDataProvider()
    {
        $entityId = 10;
        $entityClass = 'TestClass';
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        return [
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'filter' => [
                        'testOption' => 'testOption',
                    ],
                    'frontendOwnerToken' => $this->getMock(
                        'Symfony\Component\Security\Core\Authentication\Token\TokenInterface'
                    ),
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => new AccountUser(),
                ],
            ],
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'filter' => [
                        'testOption' => 'testOption',
                    ],
                    'frontendOwnerToken' => $this->getMock(
                        'Symfony\Component\Security\Core\Authentication\Token\TokenInterface'
                    ),
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => null,
                ],
            ],
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'filter' => [
                        'testOption' => 'testOption',
                    ],
                    'frontendOwnerToken' => null,
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => null,
                ],
            ]
        ];
    }

    /**
     * @dataProvider getActiveAuthorizePaymentTransactionDataProvider
     *
     * @param array $data
     * @param array $expected
     */
    public function testGetActiveAuthorizePaymentTransaction(array $data, array $expected)
    {
        $this->configureDoctrineHelper($data);

        $result = new PaymentTransaction();
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with($expected)
            ->willReturn($result);

        $this->prepareAccountUser($expected['frontendOwner'], $data['frontendOwnerToken']);

        $actual = $this->provider->getActiveAuthorizePaymentTransaction(
            $data['entity'],
            $data['amount'],
            $data['currency']
        );

        $this->assertSame($result, $actual);
    }

    /**
     * @return array
     */
    public function getActiveAuthorizePaymentTransactionDataProvider()
    {
        $entityId = 10;
        $entityClass = 'TestClass';
        $currency = 'USD';

        return [
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'currency' => $currency,
                    'amount' => 12.3456,
                    'frontendOwnerToken' => $this->getMock(
                        'Symfony\Component\Security\Core\Authentication\Token\TokenInterface'
                    )
                ],
                [
                    'active' => true,
                    'successful' => true,
                    'action' => PaymentMethodInterface::AUTHORIZE,
                    'amount' => 12.35,
                    'currency' => $currency,
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => new AccountUser(),
                ],
            ],
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'currency' => $currency,
                    'amount' => 12.3456,
                    'frontendOwnerToken' => $this->getMock(
                        'Symfony\Component\Security\Core\Authentication\Token\TokenInterface'
                    )
                ],
                [
                    'active' => true,
                    'successful' => true,
                    'action' => PaymentMethodInterface::AUTHORIZE,
                    'amount' => 12.35,
                    'currency' => $currency,
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => null,
                ],
            ],
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'currency' => $currency,
                    'amount' => 12.3456,
                    'frontendOwnerToken' => null
                ],
                [
                    'active' => true,
                    'successful' => true,
                    'action' => PaymentMethodInterface::AUTHORIZE,
                    'amount' => 12.35,
                    'currency' => $currency,
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                    'frontendOwner' => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider createPaymentTransactionDataProvider
     *
     * @param array $data
     */
    public function testCreatePaymentTransaction(array $data)
    {
        $this->configureDoctrineHelper($data);

        $transaction = $this->provider
            ->createPaymentTransaction($data['paymentMethod'], $data['type'], $data['entity']);

        $this->assertInstanceOf($this->paymentTransactionClass, $transaction);
        $this->assertEquals($data['paymentMethod'], $transaction->getPaymentMethod());
        $this->assertEquals($data['type'], $transaction->getAction());
        $this->assertEquals($data['entityClass'], $transaction->getEntityClass());
        $this->assertEquals($data['entityId'], $transaction->getEntityIdentifier());
    }

    /**
     * @return array
     */
    public function createPaymentTransactionDataProvider()
    {
        return [
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => 10,
                    'entityClass' => 'TestClass',
                    'paymentMethod' => 'testMethod',
                    'type' => 'authorize',
                ],
            ],
        ];
    }

    /**
     * @param array $data
     */
    protected function configureDoctrineHelper(array $data)
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($data['entity'])
            ->willReturn($data['entityClass']);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($data['entity'])
            ->willReturn($data['entityId']);
    }

    /**
     * @dataProvider savePaymentTransactionDataProvider
     *
     * @param PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject $transaction
     */
    public function testSavePaymentTransaction(PaymentTransaction $transaction)
    {
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($transaction)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function (\Closure $closure) use ($em, $transaction) {
                $em->expects($this->exactly($transaction->getId() ? 0 : 1))
                    ->method('persist')
                    ->with($transaction);
                
                $closure($em);
            });

        $this->provider->savePaymentTransaction($transaction);
    }

    public function testSavePaymentTransactionWithException()
    {
        $transaction = new PaymentTransaction();
        
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($transaction)
            ->willReturn($em);

        $exception = new \Exception();
        $em->expects($this->once())
            ->method('transactional')
            ->willThrowException($exception);

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $logger->expects($this->once())
            ->method('error')
            ->with($exception->getMessage(), $exception->getTrace());

        $this->provider->setLogger($logger);
        $this->provider->savePaymentTransaction($transaction);
    }

    /**
     * @return array
     */
    public function savePaymentTransactionDataProvider()
    {
        $paymentTransactionWithoutId = new PaymentTransaction();
        $paymentTransactionWithId = $this->getEntity(
            'OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction',
            ['id' => 10]
        );

        return [
            [
                $paymentTransactionWithoutId,
            ],
            [
                $paymentTransactionWithId,
            ],
        ];
    }

    /**
     * @param AccountUser|null $accountUser
     * @param null|\PHPUnit_Framework_MockObject_MockObject $token
     */
    protected function prepareAccountUser($accountUser, $token)
    {
        if ($token) {
            $token->expects($this->any())
                ->method('getUser')
                ->willReturn($accountUser);
        }

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }
}
