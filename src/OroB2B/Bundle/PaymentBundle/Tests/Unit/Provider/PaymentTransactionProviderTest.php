<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

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

        $this->provider = new PaymentTransactionProvider($this->doctrineHelper, $this->paymentTransactionClass);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->doctrineHelper, $this->repository);
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
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
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

        return [
            [
                [
                    'entity' => new \stdClass(),
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                    'filter' => [
                        'testOption' => 'testOption',
                    ],
                ],
                [
                    'testOption' => 'testOption',
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
                ],
            ],
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
                ],
                [
                    'active' => true,
                    'successful' => true,
                    'action' => PaymentMethodInterface::AUTHORIZE,
                    'amount' => 12.35,
                    'currency' => $currency,
                    'entityClass' => $entityClass,
                    'entityIdentifier' => $entityId,
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
            ->method('flush')
            ->with($transaction);

        $em->expects($transaction->getId() ? $this->never() : $this->once())
            ->method('persist')
            ->with($transaction);

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
}
