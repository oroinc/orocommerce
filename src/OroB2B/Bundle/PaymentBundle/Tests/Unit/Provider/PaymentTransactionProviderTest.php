<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PaymentTransactionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var string */
    protected $paymentTransactionClass;

    /** @var PaymentTransactionProvider */
    protected $provider;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    protected function setUp()
    {
        $this->paymentTransactionClass = 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction';

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

        $this->provider = new PaymentTransactionProvider($this->doctrineHelper, $this->paymentTransactionClass);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->doctrineHelper);
    }

    /**
     * @dataProvider getPaymentTransactionsDataProvider
     *
     * @param array $data
     * @param array $expected
     */
    public function testGetPaymentTransactions(array $data, array $expected)
    {
        $this->assertEntityIdCall($data);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with($expected);

        $this->provider->getPaymentTransactions($data['entity'], $data['filter']);
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
                        'testOption' => 'testOption'
                    ],
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId
                ]
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
        $this->assertEntityIdCall($data);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with($expected);

        $this->provider->getActiveAuthorizePaymentTransaction($data['entity'], $data['amount'], $data['currency']);
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
                    'entityClass' => 'TestClass',
                    'currency' => 'USD',
                    'amount' => 12.3456,
                ],
                [
                    'active' => true,
                    'successful' => true,
                    'action' => PaymentMethodInterface::AUTHORIZE,
                    'amount' => 12.35,
                    'currency' => $currency,
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId
                ]
            ]
        ];
    }

    /**
     * @dataProvider createPaymentTransactionDataProvider
     *
     * @param array $data
     * @param array $expected
     */
    public function testCreatePaymentTransaction(array $data, array $expected)
    {
        $this->assertEntityIdCall($data);

        $transaction = $this->provider
            ->createPaymentTransaction($data['paymentMethod'], $data['type'], $data['entity']);

        $this->assertInstanceOf($this->paymentTransactionClass, $transaction);
        $this->assertEquals($expected['paymentMethod'], $transaction->getPaymentMethod());
        $this->assertEquals($expected['action'], $transaction->getAction());
        $this->assertEquals($expected['entityClass'], $transaction->getEntityClass());
        $this->assertEquals($expected['entityIdentifier'], $transaction->getEntityIdentifier());
    }

    /**
     * @return array
     */
    public function createPaymentTransactionDataProvider()
    {
        $entityId = 10;
        $entityClass = 'TestClass';
        $type = 'USD';
        $paymentMethod = 'testMethod';

        return [
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => 'TestClass',
                    'paymentMethod' => $paymentMethod,
                    'type' => $type,
                ],
                [
                    'paymentMethod' => $paymentMethod,
                    'action' => $type,
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId
                ]
            ]
        ];
    }

    /**
     * @param array $data
     */
    protected function assertEntityIdCall(array $data)
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
     * @param integer $persist
     */
    public function testSavePaymentTransaction(PaymentTransaction $transaction, $persist)
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
            ->method('flush')
            ->with($transaction);

        $em->expects($this->exactly($persist ? 1 : 0))
            ->method('persist')
            ->with($transaction);

        $this->provider->savePaymentTransaction($transaction);
    }

    /**
     * @return array
     */
    public function savePaymentTransactionDataProvider()
    {
        /** @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject $paymentTransaction */
        $paymentTransaction = $this->getMock('OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction');
        $paymentTransaction->expects($this->any())
            ->method('getId')
            ->willReturn(10);

        return [
            [
                new PaymentTransaction(),
                true,
            ],
            [
                $paymentTransaction,
                false,
            ]
        ];
    }
}
